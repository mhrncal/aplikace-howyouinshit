<?php

/**
 * Helper funkce pro celou aplikaci
 */

use App\Core\Security;

/**
 * Redirect na URL
 */
function redirect(string $url, int $statusCode = 302): void
{
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Bezpečný výstup HTML
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Vrátí hodnotu z $_POST nebo default
 */
function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Vrátí hodnotu z $_GET nebo default
 */
function get(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/**
 * Vrátí hodnotu z $_SESSION nebo default
 */
function session(string $key, mixed $default = null): mixed
{
    return $_SESSION[$key] ?? $default;
}

/**
 * Nastaví flash message
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Získá a smaže flash message
 */
function getFlash(string $type): ?string
{
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

/**
 * Formátuje číslo jako cenu v CZK
 */
function formatPrice(?float $price): string
{
    if ($price === null) {
        return '-';
    }
    return number_format($price, 2, ',', ' ') . ' Kč';
}

/**
 * Formátuje datum
 */
function formatDate(?string $date, string $format = 'd.m.Y H:i'): string
{
    if ($date === null) {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Zkrátí text na max délku
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Vrátí base URL aplikace
 */
function baseUrl(string $path = ''): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}

/**
 * Vrátí asset URL (CSS, JS, obrázky)
 */
function asset(string $path): string
{
    return baseUrl('assets/' . ltrim($path, '/'));
}

/**
 * Include view soubor
 */
function view(string $name, array $data = []): void
{
    extract($data);
    
    $viewPath = dirname(__DIR__) . "/views/{$name}.php";
    
    if (!file_exists($viewPath)) {
        throw new \RuntimeException("View {$name} not found");
    }
    
    require $viewPath;
}

/**
 * CSRF token field pro formuláře
 */
function csrf(): string
{
    return Security::csrfField();
}

/**
 * Kontrola HTTP metody
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Debug dump (pouze pro development)
 */
function dd(mixed ...$vars): void
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Vrátí starý input po redirectu
 */
function old(string $key, mixed $default = ''): mixed
{
    $value = $_SESSION['old_input'][$key] ?? $default;
    return $value;
}

/**
 * Uloží staré inputy pro příští request
 */
function saveOldInput(): void
{
    $_SESSION['old_input'] = $_POST;
}

/**
 * Vyčistí staré inputy
 */
function clearOldInput(): void
{
    unset($_SESSION['old_input']);
}

/**
 * Validace a vrácení chyb
 */
function getErrors(string $key = null): array|string|null
{
    if ($key === null) {
        return $_SESSION['errors'] ?? [];
    }
    
    $error = $_SESSION['errors'][$key] ?? null;
    return $error;
}

/**
 * Nastaví chyby validace
 */
function setErrors(array $errors): void
{
    $_SESSION['errors'] = $errors;
}

/**
 * Vyčistí chyby
 */
function clearErrors(): void
{
    unset($_SESSION['errors']);
}

/**
 * Paginace helper
 */
function paginate(int $total, int $perPage = 20, int $currentPage = 1): array
{
    $totalPages = (int) ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_more' => $currentPage < $totalPages,
        'has_prev' => $currentPage > 1,
    ];
}

/**
 * ====================================
 * STORE HELPERS
 * ====================================
 */

/**
 * Získej ID aktuálního shopu
 */
function currentStoreId(): ?int
{
    return $_SESSION['current_store_id'] ?? null;
}

/**
 * Získej aktuální shop
 */
function currentStore(): ?array
{
    global $currentStore;
    return $currentStore;
}

/**
 * Přepni na jiný shop
 */
function switchStore(int $storeId): void
{
    global $auth;
    
    if (!$auth->check()) {
        return;
    }
    
    // Ověř že shop patří uživateli
    $storeModel = new \App\Models\Store();
    $store = $storeModel->findById($storeId, $auth->userId());
    
    if ($store && $store['is_active']) {
        $_SESSION['current_store_id'] = $storeId;
    }
}

/**
 * Získej všechny shopy aktuálního uživatele
 */
function userStores(): array
{
    global $auth;
    
    if (!$auth->check()) {
        return [];
    }
    
    $storeModel = new \App\Models\Store();
    return $storeModel->getActiveForUser($auth->userId());
}

/**
 * Získej store_id pro použití v queries
 * Pokud není zadán, použije aktuální
 */
function getStoreIdForQuery(?int $storeId = null): ?int
{
    return $storeId ?? currentStoreId();
}

/**
 * Přidej store_id do dat před insert/update
 */
function addStoreToData(array $data, ?int $storeId = null): array
{
    $storeId = getStoreIdForQuery($storeId);
    
    if ($storeId !== null && !isset($data['store_id'])) {
        $data['store_id'] = $storeId;
    }
    
    return $data;
}
