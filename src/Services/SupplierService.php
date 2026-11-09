<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SupplierRepo;
use InvalidArgumentException;
use Throwable;

class SupplierService
{
    private SupplierRepo $supplierRepo;

    private const NAME_MAX          = 150;
    private const CONTACT_PERSON_MAX = 150;
    private const EMAIL_MAX          = 255;
    private const PHONE_MAX          = 30;

    public function __construct(SupplierRepo $supplierRepo)
    {
        $this->supplierRepo = $supplierRepo;
    }

    // ==========================================
    // GET ALL SUPPLIERS
    // ==========================================

    public function getAllSuppliers(): array
    {
        return $this->supplierRepo->getAllSuppliers();
    }

    // ==========================================
    // FIND SUPPLIER BY ID
    // ==========================================

    public function getSupplierById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $supplier = $this->supplierRepo->getSupplierById($id);

        return $supplier === false ? null : $supplier;
    }

    // ==========================================
    // COUNT ORDERS LINKED TO A SUPPLIER
    // ==========================================

    public function countSupplierOrders(int $supplierId): int
    {
        if ($supplierId <= 0) {
            return 0;
        }

        return $this->supplierRepo->countOrdersForSupplier($supplierId);
    }

    // ==========================================
    // CREATE SUPPLIER
    // RETURNS NEW SUPPLIER ID, OR NULL IF INPUT IS REJECTED
    // THROWS InvalidArgumentException WITH A USER-FACING
    // MESSAGE ON VALIDATION ERRORS AND DUPLICATE NAME
    // ==========================================

    public function createSupplier(
        string $name,
        string $contactPerson = '',
        string $email         = '',
        string $phone         = '',
        string $address       = ''
    ): ?int {
        $data = $this->normalise($name, $contactPerson, $email, $phone, $address);

        if ($this->supplierRepo->findSupplierByName($data['name']) !== false) {
            throw new InvalidArgumentException('A supplier with that name already exists.');
        }

        try {
            $id = $this->supplierRepo->createSupplier(
                $data['name'],
                $data['contactPerson'],
                $data['email'],
                $data['phone'],
                $data['address']
            );
        } catch (Throwable $e) {
            error_log('Supplier create error: ' . $e->getMessage());
            throw new InvalidArgumentException('Unable to create the supplier right now.');
        }

        return $id > 0 ? $id : null;
    }

    // ==========================================
    // UPDATE SUPPLIER BY ID
    // ==========================================

    public function updateSupplierById(
        int $supplierId,
        string $name,
        string $contactPerson = '',
        string $email         = '',
        string $phone         = '',
        string $address       = ''
    ): bool {
        if ($supplierId <= 0) {
            return false;
        }

        $data = $this->normalise($name, $contactPerson, $email, $phone, $address);

        $existing = $this->supplierRepo->getSupplierById($supplierId);
        if ($existing === false) {
            throw new InvalidArgumentException('Supplier not found.');
        }

        $duplicate = $this->supplierRepo->findSupplierByName($data['name']);
        if ($duplicate !== false && (int) $duplicate['id'] !== $supplierId) {
            throw new InvalidArgumentException('A supplier with that name already exists.');
        }

        try {
            return $this->supplierRepo->updateSupplierById(
                $supplierId,
                $data['name'],
                $data['contactPerson'],
                $data['email'],
                $data['phone'],
                $data['address']
            );
        } catch (Throwable $e) {
            error_log('Supplier update error: ' . $e->getMessage());
            throw new InvalidArgumentException('Unable to update the supplier right now.');
        }
    }

    // ==========================================
    // DELETE SUPPLIER BY ID
    // BLOCKED WHILE ORDERS STILL REFERENCE IT
    // ==========================================

    public function deleteSupplierById(int $supplierId): bool
    {
        if ($supplierId <= 0) {
            return false;
        }

        $supplier = $this->supplierRepo->getSupplierById($supplierId);
        if ($supplier === false) {
            throw new InvalidArgumentException('Supplier not found.');
        }

        $orderCount = $this->supplierRepo->countOrdersForSupplier($supplierId);
        if ($orderCount > 0) {
            throw new InvalidArgumentException(
                'This supplier has ' . $orderCount . ' restock order' .
                ($orderCount === 1 ? '' : 's') .
                ' linked to it and cannot be deleted.'
            );
        }

        try {
            return $this->supplierRepo->deleteSupplierById($supplierId);
        } catch (Throwable $e) {
            error_log('Supplier delete error: ' . $e->getMessage());
            throw new InvalidArgumentException('Unable to delete the supplier right now.');
        }
    }

    // ==========================================
    // VALIDATE + TRIM INPUT
    // THROWS InvalidArgumentException WITH A
    // USER-FACING MESSAGE ON BAD INPUT
    // ==========================================

    private function normalise(
        string $name,
        string $contactPerson,
        string $email,
        string $phone,
        string $address
    ): array {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Supplier name is required.');
        }

        if (mb_strlen($name) > self::NAME_MAX) {
            throw new InvalidArgumentException('Supplier name is too long (max ' . self::NAME_MAX . ' characters).');
        }

        $contactPerson = trim($contactPerson);
        if (mb_strlen($contactPerson) > self::CONTACT_PERSON_MAX) {
            throw new InvalidArgumentException('Contact person is too long (max ' . self::CONTACT_PERSON_MAX . ' characters).');
        }

        $email = trim($email);
        if ($email !== '') {
            if (mb_strlen($email) > self::EMAIL_MAX || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('The supplier email address is not valid.');
            }
        }

        $phone = trim($phone);
        if (mb_strlen($phone) > self::PHONE_MAX) {
            throw new InvalidArgumentException('Phone number is too long (max ' . self::PHONE_MAX . ' characters).');
        }

        $address = trim($address);

        return [
            'name'           => $name,
            'contactPerson'  => $contactPerson !== '' ? $contactPerson : null,
            'email'          => $email !== '' ? $email : null,
            'phone'          => $phone !== '' ? $phone : null,
            'address'        => $address !== '' ? $address : null,
        ];
    }
}
