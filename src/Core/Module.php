<?php

namespace App\Core;

/**
 * Base třída pro moduly
 * Každý modul může rozšířit tuto třídu
 */
abstract class Module
{
    protected Database $db;
    protected Auth $auth;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Render view pro modul
     */
    protected function render(string $view, array $data = []): void
    {
        $data['auth'] = $this->auth;
        extract($data);
        
        $viewPath = dirname(__DIR__, 2) . "/views/{$view}.php";
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }
        
        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        
        require dirname(__DIR__, 2) . '/views/layouts/main.php';
    }
    
    /**
     * JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Validace POST požadavku
     */
    protected function validatePost(): bool
    {
        return isPost() && Security::verifyCsrfToken(post('csrf_token'));
    }
    
    /**
     * Kontrola oprávnění
     */
    protected function requireAuth(): void
    {
        $this->auth->requireAuth();
    }
    
    /**
     * Kontrola admin oprávnění
     */
    protected function requireAdmin(): void
    {
        $this->auth->requireSuperAdmin();
    }
}
