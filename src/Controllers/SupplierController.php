<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SupplierService;
use App\Services\AuthService;
use InvalidArgumentException;
use Throwable;

class SupplierController
{
    public function __construct(
        private SupplierService $supplierService,
        private AuthService $authService
    ) {}

    // ==========================================
    // POST GUARDS (same contract as OrderController)
    // ==========================================

    private function requireValidRequest(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || !$this->authService->verifyCsrfToken($token)) {
            http_response_code(403);
            exit('Invalid security token.');
        }
        if (!$this->authService->isAuthenticated()) {
            http_response_code(401);
            exit('Authentication required.');
        }
    }

    /**
     * Where to land back on the orders page:
     * 'suppliers' (default) reopens the suppliers modal,
     * 'new-order' reopens the new restock order modal.
     */
    private function returnTarget(): string
    {
        $target = $_POST['return_to'] ?? $_GET['return_to'] ?? 'suppliers';
        return $target === 'new-order' ? 'new-order' : 'suppliers';
    }

    private function redirect(string $message, bool $success, string $returnTo = 'suppliers'): never
    {
        $_SESSION[$success ? 'success' : 'error'] = $message;
        header('Location: /orders?modal=' . $returnTo, true, 303);
        exit;
    }

    private function postString(string $field): string
    {
        return is_string($_POST[$field] ?? null) ? (string) $_POST[$field] : '';
    }

    private function postId(string $field): ?int
    {
        $value = filter_var($_POST[$field] ?? null, FILTER_VALIDATE_INT);
        return is_int($value) && $value > 0 ? $value : null;
    }

    // ==========================================
    // POST /suppliers/create — add a new supplier
    // ==========================================

    public function store(): void
    {
        $this->requireValidRequest();
        $returnTo = $this->returnTarget();

        try {
            $id = $this->supplierService->createSupplier(
                $this->postString('name'),
                $this->postString('contact_person'),
                $this->postString('email'),
                $this->postString('phone'),
                $this->postString('address')
            );

            if ($id === null) {
                $this->redirect('Unable to save supplier.', false, $returnTo);
            }

            $this->redirect('Supplier added.', true, $returnTo);
        } catch (InvalidArgumentException $e) {
            $this->redirect($e->getMessage(), false, $returnTo);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Database error while saving supplier.', false, $returnTo);
        }
    }

    // ==========================================
    // POST /suppliers/update — edit an existing supplier
    // ==========================================

    public function update(): void
    {
        $this->requireValidRequest();
        $supplierId = $this->postId('supplier_id');
        if ($supplierId === null) {
            $this->redirect('Invalid supplier.', false);
        }

        try {
            if ($this->supplierService->getSupplierById($supplierId) === null) {
                $this->redirect('Supplier not found.', false);
            }

            $this->supplierService->updateSupplierById(
                $supplierId,
                $this->postString('name'),
                $this->postString('contact_person'),
                $this->postString('email'),
                $this->postString('phone'),
                $this->postString('address')
            );

            $this->redirect('Supplier updated.', true);
        } catch (InvalidArgumentException $e) {
            $this->redirect($e->getMessage(), false);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Database error while updating supplier.', false);
        }
    }

    // ==========================================
    // POST /suppliers/delete — FK-safe removal
    // ==========================================

    public function destroy(): void
    {
        $this->requireValidRequest();
        $supplierId = $this->postId('supplier_id');
        if ($supplierId === null) {
            $this->redirect('Invalid supplier.', false);
        }

        try {
            $this->supplierService->deleteSupplierById($supplierId);
            $this->redirect('Supplier deleted.', true);
        } catch (InvalidArgumentException $e) {
            $this->redirect($e->getMessage(), false);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to delete supplier — it may still be referenced by orders.', false);
        }
    }
}
