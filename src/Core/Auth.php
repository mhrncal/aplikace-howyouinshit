<?php

namespace App\Core;

use App\Models\User;

/**
 * Autentizační systém s podporou reset hesla
 */
class Auth
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Přihlášení uživatele
     */
    public function login(string $email, string $password): bool
    {
        // Rate limiting
        if (!Security::rateLimit('login_' . $email, 5, 300)) {
            Logger::warning('Too many login attempts', ['email' => $email]);
            return false;
        }

        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1",
            [$email]
        );

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            Logger::warning('Failed login attempt', ['email' => $email]);
            return false;
        }

        // Nastavení session
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_super_admin'] = (bool)$user['is_super_admin'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        Logger::info('User logged in', ['user_id' => $user['id'], 'email' => $email]);
        
        return true;
    }

    /**
     * Odhlášení uživatele
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        session_destroy();
        session_start();
        session_regenerate_id(true);

        if ($userId) {
            Logger::info('User logged out', ['user_id' => $userId]);
        }
    }

    /**
     * Kontrola, zda je uživatel přihlášen
     */
    public function check(): bool
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }

        // Session timeout - 2 hodiny
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Vyžaduje přihlášení
     */
    public function requireAuth(): void
    {
        if (!$this->check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            redirect('/login.php');
        }
    }

    /**
     * Vyžaduje Super Admin
     */
    public function requireSuperAdmin(): void
    {
        $this->requireAuth();
        
        if (!$this->isSuperAdmin()) {
            Logger::warning('Unauthorized access attempt to admin area', [
                'user_id' => $this->userId()
            ]);
            http_response_code(403);
            die('Přístup odepřen. Vyžadována Super Admin role.');
        }
    }

    /**
     * Vrátí přihlášeného uživatele
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'is_super_admin' => $_SESSION['is_super_admin']
        ];
    }

    /**
     * Vrátí ID přihlášeného uživatele
     */
    public function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Je přihlášený uživatel Super Admin?
     */
    public function isSuperAdmin(): bool
    {
        return isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] === true;
    }

    /**
     * Vytvoří reset token pro heslo
     */
    public function createPasswordResetToken(string $email): ?string
    {
        $user = $this->db->fetchOne(
            "SELECT id, email FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$user) {
            // Security: Nesdělujeme, že email neexistuje
            return null;
        }

        // Rate limiting pro reset hesla
        if (!Security::rateLimit('password_reset_' . $email, 3, 3600)) {
            Logger::warning('Too many password reset attempts', ['email' => $email]);
            return null;
        }

        $token = Security::generateToken(32);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hodina platnost

        // Uložení tokenu do databáze
        $this->db->query(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE token = ?, expires_at = ?, created_at = NOW()",
            [$user['id'], $token, $expiresAt, $token, $expiresAt]
        );

        Logger::info('Password reset token created', ['user_id' => $user['id']]);

        return $token;
    }

    /**
     * Ověří reset token
     */
    public function verifyPasswordResetToken(string $token): ?int
    {
        $result = $this->db->fetchOne(
            "SELECT user_id FROM password_reset_tokens 
             WHERE token = ? AND expires_at > NOW() AND used_at IS NULL",
            [$token]
        );

        return $result['user_id'] ?? null;
    }

    /**
     * Resetuje heslo pomocí tokenu
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $userId = $this->verifyPasswordResetToken($token);

        if (!$userId) {
            return false;
        }

        // Validace nového hesla
        $errors = Security::validatePassword($newPassword);
        if (!empty($errors)) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            // Update hesla
            $this->db->update(
                'users',
                ['password' => Security::hashPassword($newPassword)],
                'id = ?',
                [$userId]
            );

            // Označení tokenu jako použitého
            $this->db->update(
                'password_reset_tokens',
                ['used_at' => date('Y-m-d H:i:s')],
                'token = ?',
                [$token]
            );

            $this->db->commit();

            Logger::info('Password reset successful', ['user_id' => $userId]);

            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::error('Password reset failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Změní heslo přihlášeného uživatele
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->db->fetchOne(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user || !Security::verifyPassword($currentPassword, $user['password'])) {
            return false;
        }

        $errors = Security::validatePassword($newPassword);
        if (!empty($errors)) {
            return false;
        }

        $this->db->update(
            'users',
            ['password' => Security::hashPassword($newPassword)],
            'id = ?',
            [$userId]
        );

        Logger::info('Password changed', ['user_id' => $userId]);

        return true;
    }
}
