<?php

namespace App\Core;

/**
 * Bezpečnostní třída - CSRF, XSS, validace
 */
class Security
{
    /**
     * Generuje CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Ověří CSRF token
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || $token === null) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Vygeneruje HTML input pro CSRF token
     */
    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Vyčistí vstup od XSS
     */
    public static function clean(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([self::class, 'clean'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }

    /**
     * Validace emailu
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validace hesla (min 8 znaků, velké/malé písmeno, číslo)
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Heslo musí mít alespoň 8 znaků';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Heslo musí obsahovat alespoň jedno velké písmeno';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Heslo musí obsahovat alespoň jedno malé písmeno';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Heslo musí obsahovat alespoň jednu číslici';
        }
        
        return $errors;
    }

    /**
     * Hash hesla - s fallback na BCRYPT pokud Argon2id není dostupný
     */
    public static function hashPassword(string $password): string
    {
        // Zkus Argon2id (bezpečnější)
        if (defined('PASSWORD_ARGON2ID')) {
            try {
                $hash = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 2
                ]);
                if ($hash !== false) {
                    return $hash;
                }
            } catch (\Exception $e) {
                // Fallback na BCRYPT
            }
        }
        
        // Fallback: BCRYPT (funguje všude)
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }

    /**
     * Ověří heslo
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Validace IČO (česká verze)
     */
    public static function validateIco(?string $ico): bool
    {
        if ($ico === null || $ico === '') {
            return true; // IČO je volitelné
        }
        
        $ico = preg_replace('/\s+/', '', $ico);
        
        if (!preg_match('/^\d{8}$/', $ico)) {
            return false;
        }
        
        // Kontrolní součet
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$ico[$i] * (8 - $i);
        }
        
        $remainder = $sum % 11;
        $checkDigit = (11 - $remainder) % 10;
        
        return (int)$ico[7] === $checkDigit;
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return substr($filename, 0, 255);
    }

    /**
     * Rate limiting - jednoduché pomocí session
     */
    public static function rateLimit(string $key, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $sessionKey = "rate_limit_{$key}";
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [
                'attempts' => 0,
                'reset_at' => time() + $timeWindow
            ];
        }
        
        $data = $_SESSION[$sessionKey];
        
        // Reset pokud vypršel čas
        if (time() > $data['reset_at']) {
            $_SESSION[$sessionKey] = [
                'attempts' => 1,
                'reset_at' => time() + $timeWindow
            ];
            return true;
        }
        
        // Kontrola limitu
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        $_SESSION[$sessionKey]['attempts']++;
        return true;
    }

    /**
     * Generuje bezpečný náhodný token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}
