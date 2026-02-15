<?php

namespace App\Core;

/**
 * PSR-4 Autoloader pro čisté PHP bez Composeru
 */
class Autoloader
{
    private static array $prefixes = [];
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register([self::class, 'load']);
        
        // Registrace namespace App -> src/
        self::addNamespace('App', dirname(__DIR__));
        
        self::$registered = true;
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }
        
        self::$prefixes[$prefix][] = $baseDir;
    }

    public static function load(string $class): void
    {
        $prefix = $class;
        
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            
            $mappedFile = self::loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return;
            }
            
            $prefix = rtrim($prefix, '\\');
        }
    }

    private static function loadMappedFile(string $prefix, string $relativeClass): ?string
    {
        if (!isset(self::$prefixes[$prefix])) {
            return null;
        }

        foreach (self::$prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            
            if (self::requireFile($file)) {
                return $file;
            }
        }
        
        return null;
    }

    private static function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}
