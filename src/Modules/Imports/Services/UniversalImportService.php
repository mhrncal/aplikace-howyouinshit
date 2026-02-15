<?php

namespace App\Modules\Imports\Services;

use App\Modules\Products\Services\FlexibleXmlParser;
use App\Modules\Orders\Services\OrderXmlParser;
use App\Core\Logger;

/**
 * Univerzální import router - rozhodne podle typu feedu
 */
class UniversalImportService
{
    /**
     * Importuje feed podle typu
     */
    public function import(int $feedSourceId, int $userId, string $url, string $feedType, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        Logger::info('Universal import started', [
            'feed_source_id' => $feedSourceId,
            'user_id' => $userId,
            'feed_type' => $feedType
        ]);
        
        // Podle typu feedu zvol správný parser
        $result = match($feedType) {
            'shoptet_products', 'products_xml' => $this->importProducts($feedSourceId, $userId, $url, $httpAuthUser, $httpAuthPass),
            'shoptet_orders', 'orders_xml' => $this->importOrders($feedSourceId, $userId, $url, $httpAuthUser, $httpAuthPass),
            'shoptet_stock', 'stock_xml' => $this->importStock($feedSourceId, $userId, $url, $httpAuthUser, $httpAuthPass),
            'shoptet_prices', 'prices_xml' => $this->importPrices($feedSourceId, $userId, $url, $httpAuthUser, $httpAuthPass),
            default => throw new \Exception("Neznámý typ feedu: {$feedType}")
        };
        
        Logger::info('Universal import completed', [
            'feed_type' => $feedType,
            'result' => $result
        ]);
        
        return $result;
    }
    
    /**
     * Import produktů
     */
    private function importProducts(int $feedSourceId, int $userId, string $url, ?string $httpAuthUser, ?string $httpAuthPass): array
    {
        $xmlImportService = new \App\Modules\Products\Services\XmlImportService();
        
        return $xmlImportService->importFromUrl(
            $feedSourceId,
            $userId,
            $url,
            $httpAuthUser,
            $httpAuthPass
        );
    }
    
    /**
     * Import objednávek
     */
    private function importOrders(int $feedSourceId, int $userId, string $url, ?string $httpAuthUser, ?string $httpAuthPass): array
    {
        // TODO: Implementovat OrderXmlParser
        Logger::info('Order import not yet implemented');
        
        return [
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
        ];
    }
    
    /**
     * Import skladů
     */
    private function importStock(int $feedSourceId, int $userId, string $url, ?string $httpAuthUser, ?string $httpAuthPass): array
    {
        // TODO: Implementovat StockXmlParser
        Logger::info('Stock import not yet implemented');
        
        return [
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
        ];
    }
    
    /**
     * Import cen
     */
    private function importPrices(int $feedSourceId, int $userId, string $url, ?string $httpAuthUser, ?string $httpAuthPass): array
    {
        // TODO: Implementovat PriceXmlParser
        Logger::info('Price import not yet implemented');
        
        return [
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
        ];
    }
}
