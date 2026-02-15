<?php

/**
 * Aplikační konfigurace
 */
return [
    'name' => 'E-shop Analytics',
    'version' => '2.0',
    'environment' => 'production', // development | production
    'debug' => false,
    
    'timezone' => 'Europe/Prague',
    'locale' => 'cs_CZ',
    
    // Session
    'session_lifetime' => 7200, // 2 hodiny v sekundách
    'session_name' => 'ESHOP_ANALYTICS_SESSION',
    
    // Email (pro reset hesla)
    'mail' => [
        'from_email' => 'noreply@eshop-analytics.cz',
        'from_name' => 'E-shop Analytics',
    ],
    
    // Pagination
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],
    
    // Import
    'import' => [
        'batch_size' => 500,
        'memory_limit' => '512M',
        'max_execution_time' => 300,
    ],
    
    // Cache
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hodina
    ],
];
