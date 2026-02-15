<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Security;
use App\Core\Logger;

/**
 * User model s kompletní správou uživatelů
 */
class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Získá všechny uživatele (jen pro Super Admin)
     */
    public function getAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $users = $this->db->fetchAll(
            "SELECT id, name, email, is_super_admin, is_active, company_name, 
                    ico, dic, phone, created_at, updated_at
             FROM users 
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $total = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];

        return [
            'users' => $users,
            'pagination' => paginate($total, $perPage, $page)
        ];
    }

    /**
     * Získá uživatele podle ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, name, email, is_super_admin, is_active, company_name, 
                    ico, dic, phone, address, city, zip, country, created_at, updated_at
             FROM users 
             WHERE id = ?",
            [$id]
        );
    }

    /**
     * Získá uživatele podle emailu
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    /**
     * Vytvoří nového uživatele
     */
    public function create(array $data): int|false
    {
        // Validace
        $errors = $this->validate($data);
        if (!empty($errors)) {
            setErrors($errors);
            return false;
        }

        // Kontrola, zda email už neexistuje
        if ($this->findByEmail($data['email'])) {
            setErrors(['email' => 'Email už je zaregistrován']);
            return false;
        }

        try {
            $userId = $this->db->insert('users', [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Security::hashPassword($data['password']),
                'is_super_admin' => $data['is_super_admin'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'company_name' => $data['company_name'] ?? null,
                'ico' => $data['ico'] ?? null,
                'dic' => $data['dic'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? 'Česká republika',
            ]);

            Logger::info('User created', ['user_id' => $userId, 'email' => $data['email']]);

            return $userId;

        } catch (\Exception $e) {
            Logger::error('User creation failed', ['error' => $e->getMessage()]);
            setErrors(['general' => 'Vytvoření uživatele selhalo']);
            return false;
        }
    }

    /**
     * Aktualizuje uživatele
     */
    public function update(int $id, array $data): bool
    {
        // Validace
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            setErrors($errors);
            return false;
        }

        // Kontrola duplicitního emailu
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$data['email'], $id]
        );

        if ($existing) {
            setErrors(['email' => 'Email už je používán jiným uživatelem']);
            return false;
        }

        try {
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'company_name' => $data['company_name'] ?? null,
                'ico' => $data['ico'] ?? null,
                'dic' => $data['dic'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'zip' => $data['zip'] ?? null,
                'country' => $data['country'] ?? 'Česká republika',
            ];

            // Pouze super admin může měnit tyto hodnoty
            if (isset($data['is_super_admin'])) {
                $updateData['is_super_admin'] = $data['is_super_admin'];
            }
            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            // Změna hesla je volitelná
            if (!empty($data['password'])) {
                $passwordErrors = Security::validatePassword($data['password']);
                if (!empty($passwordErrors)) {
                    setErrors(['password' => implode(', ', $passwordErrors)]);
                    return false;
                }
                $updateData['password'] = Security::hashPassword($data['password']);
            }

            $this->db->update('users', $updateData, 'id = ?', [$id]);

            Logger::info('User updated', ['user_id' => $id]);

            return true;

        } catch (\Exception $e) {
            Logger::error('User update failed', ['error' => $e->getMessage()]);
            setErrors(['general' => 'Aktualizace uživatele selhala']);
            return false;
        }
    }

    /**
     * Smaže uživatele
     */
    public function delete(int $id): bool
    {
        try {
            // Nelze smazat sám sebe
            if ($id === ($_SESSION['user_id'] ?? null)) {
                setErrors(['general' => 'Nemůžete smazat sám sebe']);
                return false;
            }

            $this->db->delete('users', 'id = ?', [$id]);

            Logger::info('User deleted', ['user_id' => $id]);

            return true;

        } catch (\Exception $e) {
            Logger::error('User deletion failed', ['error' => $e->getMessage()]);
            setErrors(['general' => 'Smazání uživatele selhalo']);
            return false;
        }
    }

    /**
     * Validace dat uživatele
     */
    private function validate(array $data, ?int $userId = null): array
    {
        $errors = [];

        // Jméno
        if (empty($data['name']) || strlen($data['name']) < 2) {
            $errors['name'] = 'Jméno musí mít alespoň 2 znaky';
        }

        // Email
        if (empty($data['email']) || !Security::validateEmail($data['email'])) {
            $errors['email'] = 'Neplatný email';
        }

        // Heslo (pouze při vytváření nebo pokud je zadané)
        if ($userId === null || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'Heslo je povinné';
            } else {
                $passwordErrors = Security::validatePassword($data['password']);
                if (!empty($passwordErrors)) {
                    $errors['password'] = implode(', ', $passwordErrors);
                }
            }
        }

        // IČO (volitelné, ale pokud je vyplněné, musí být validní)
        if (!empty($data['ico']) && !Security::validateIco($data['ico'])) {
            $errors['ico'] = 'Neplatné IČO';
        }

        return $errors;
    }

    /**
     * Počet uživatelů
     */
    public function count(): int
    {
        return (int) $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
    }

    /**
     * Počet aktivních uživatelů
     */
    public function countActive(): int
    {
        return (int) $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
    }

    /**
     * Toggle active status
     */
    public function toggleActive(int $id): bool
    {
        try {
            $user = $this->findById($id);
            if (!$user) {
                return false;
            }

            $newStatus = !$user['is_active'];
            
            $this->db->update('users', ['is_active' => $newStatus], 'id = ?', [$id]);

            Logger::info('User status toggled', ['user_id' => $id, 'is_active' => $newStatus]);

            return true;

        } catch (\Exception $e) {
            Logger::error('Toggle active failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
