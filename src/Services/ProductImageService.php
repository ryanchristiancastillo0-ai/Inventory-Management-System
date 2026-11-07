<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Product Image Upload Service
 *
 * Native PHP equivalent of Multer-style file handling: validates an
 * uploaded image from $_FILES, gives it a random unreadable original
 * filename, and moves it into public/uploads/products.
 *
 * Security notes:
 * - The actual MIME type is verified with finfo_file (never trusting
 *   the client-supplied name or Content-Type).
 * - getimagesize() confirms the payload is a real image and bounds
 *   absurd dimensions.
 * - Stored filenames come from random_bytes(); the user's original
 *   filename is never reused (no traversal, no collisions).
 * - Only files under the managed uploads directory are ever touched
 *   or deleted.
 * - The DB stores only the relative public path (e.g.
 *   /uploads/products/ab12cd34....png), never the binary.
 */
class ProductImageService
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    private const MAX_DIMENSION = 8000;        // px, per side

    /** Mime types we accept, mapped to the stored file extension. */
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private string $uploadDir;
    private string $baseUrl;

    /** @param string|null $uploadDir testing override only. */
    public function __construct(?string $uploadDir = null)
    {
        $root = dirname(__DIR__, 2); // project root
        $this->uploadDir = $uploadDir ?? $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $this->baseUrl = '/uploads/products/';
    }

    /**
     * Validate and store an uploaded product image.
     *
     * @param array $file one entry from $_FILES, e.g. $_FILES['image']
     *
     * @return array{success: bool, message: string, path: string|null, errorCode: string|null}
     *         path is the relative public path to persist in MySQL.
     */
    public function store(array $file): array
    {
        // -- Upload transfer-level checks -------------------------------------
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'No image was selected.', 'path' => null, 'errorCode' => 'NO_FILE'];
        }

        if ($error !== UPLOAD_ERR_OK) {
            // Server-side size cap can also raise UPLOAD_ERR_INI_SIZE /
            // UPLOAD_ERR_FORM_SIZE before we ever see the bytes.
            $message = $error === UPLOAD_ERR_INI_SIZE
                ? 'The image exceeds the server upload limit.'
                : 'The image upload failed. Please try again.';
            return ['success' => false, 'message' => $message, 'path' => null, 'errorCode' => 'UPLOAD_ERR_' . $error];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($tmpPath === '' || $size <= 0) {
            return ['success' => false, 'message' => 'The image upload failed. Please try again.', 'path' => null, 'errorCode' => 'NOT_UPLOAD'];
        }

        // -- Size limit --------------------------------------------------------
        if ($size > self::MAX_BYTES) {
            return ['success' => false, 'message' => 'The image is too large. Maximum size is 2 MB.', 'path' => null, 'errorCode' => 'TOO_LARGE'];
        }

        // -- Real MIME type (never trust the filename / client header) --------
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return ['success' => false, 'message' => 'The image could not be verified. Please try again.', 'path' => null, 'errorCode' => 'FINFO_FAIL'];
        }
        $mime = (string) finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $extension = self::ALLOWED_MIME[$mime] ?? null;

        if ($extension === null) {
            return ['success' => false, 'message' => 'Only JPEG, PNG or WebP images are supported.', 'path' => null, 'errorCode' => 'BAD_TYPE'];
        }

        // -- Confirms a real, decodable image + bounds dimensions -------------
        $info = @getimagesize($tmpPath);

        if ($info === false || ($info[0] ?? 0) <= 0 || ($info[1] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'This file does not appear to be a valid image.', 'path' => null, 'errorCode' => 'CORRUPT'];
        }

        if (($info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION)) {
            return ['success' => false, 'message' => 'The image dimensions are too large. Maximum is 8000×8000 pixels.', 'path' => null, 'errorCode' => 'TOO_BIG_DIMENSIONS'];
        }

        // -- Storage directory --------------------------------------------------
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            return ['success' => false, 'message' => 'Image storage is unavailable. Please try again later.', 'path' => null, 'errorCode' => 'MKDIR_FAIL'];
        }

        if (!is_writable($this->uploadDir)) {
            return ['success' => false, 'message' => 'Image storage is unavailable. Please try again later.', 'path' => null, 'errorCode' => 'NOT_WRITABLE'];
        }

        // -- Random filename; original name is never reused --------------------
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!$this->doMove($tmpPath, $destination)) {
            return ['success' => false, 'message' => 'The image could not be saved. Please try again.', 'path' => null, 'errorCode' => 'MOVE_FAIL'];
        }

        @chmod($destination, 0644);

        return ['success' => true, 'message' => 'Image stored.', 'path' => $this->baseUrl . $filename, 'errorCode' => null];
    }

    /**
     * The actual move step: is_uploaded_file() guards against
     * crafted paths in tampered requests — only genuine upload
     * temp files are accepted.
     */
    protected function doMove(string $tmpPath, string $destination): bool
    {
        if (!is_uploaded_file($tmpPath)) {
            return false;
        }
        return move_uploaded_file($tmpPath, $destination);
    }

    /**
     * Delete a previously stored product image.
     *
     * Accepts the relative public path stored in MySQL. Returns only
     * after confirming the resolved file lives inside the managed
     * uploads directory — traversal and outside paths are rejected.
     */
    public function delete(?string $relativePath): bool
    {
        if ($relativePath === null || trim($relativePath) === '' || !str_starts_with($relativePath, $this->baseUrl)) {
            return false;
        }

        // basename() strips any directory component a stored path might carry.
        $filename = basename($relativePath);

        if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename)) {
            return false;
        }

        $absolute = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;
        $realUploadDir = realpath($this->uploadDir);
        $realAbsolute = realpath($absolute);

        if (
            $realUploadDir === false
            || $realAbsolute === false
            || !str_starts_with($realAbsolute, $realUploadDir . DIRECTORY_SEPARATOR)
        ) {
            return false;
        }

        return @unlink($realAbsolute);
    }
}
