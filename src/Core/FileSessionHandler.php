<?php

namespace App\Core;

/**
 * Custom File Session Handler
 * Pro případy kdy server má sessions vypnuté
 */
class FileSessionHandler implements \SessionHandlerInterface
{
    private string $savePath;
    
    public function open($savePath, $sessionName): bool
    {
        $this->savePath = __DIR__ . '/../../storage/sessions';
        
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }
        
        return true;
    }
    
    public function close(): bool
    {
        return true;
    }
    
    public function read($id): string|false
    {
        $file = $this->savePath . '/sess_' . $id;
        
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        
        return '';
    }
    
    public function write($id, $data): bool
    {
        $file = $this->savePath . '/sess_' . $id;
        return file_put_contents($file, $data) !== false;
    }
    
    public function destroy($id): bool
    {
        $file = $this->savePath . '/sess_' . $id;
        
        if (file_exists($file)) {
            unlink($file);
        }
        
        return true;
    }
    
    public function gc($maxlifetime): int|false
    {
        $count = 0;
        $files = glob($this->savePath . '/sess_*');
        
        foreach ($files as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}
