<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\Product;
use App\Core\Database;

/**
 * Export produktů do CSV/XLSX s custom poli
 */
class ProductExporter
{
    private Database $db;
    private Product $productModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->productModel = new Product();
    }
    
    /**
     * Exportuje produkty do CSV
     */
    public function exportToCsv(int $userId, array $filters = []): string
    {
        $products = $this->getProductsForExport($userId, $filters);
        
        if (empty($products)) {
            return '';
        }
        
        // Získej všechny custom fields které se vyskytují
        $customFields = $this->getAllCustomFields($products);
        
        // Hlavička CSV
        $headers = array_merge(
            ['ID', 'Název', 'Kód', 'Cena s DPH', 'Kategorie', 'URL', 'Obrázek'],
            $customFields
        );
        
        // Vytvoř CSV
        $output = fopen('php://temp', 'r+');
        
        // BOM pro Excel UTF-8 support
        fputs($output, "\xEF\xBB\xBF");
        
        // Hlavička
        fputcsv($output, $headers, ';');
        
        // Data
        foreach ($products as $product) {
            $customData = json_decode($product['custom_data'] ?? '{}', true);
            
            $row = [
                $product['id'],
                $product['name'],
                $product['code'] ?? '',
                $product['price_vat'] ?? 0,
                $product['category'] ?? '',
                $product['url'] ?? '',
                $product['image_url'] ?? '',
            ];
            
            // Přidej custom fields
            foreach ($customFields as $field) {
                $row[] = $customData[$field] ?? '';
            }
            
            fputcsv($output, $row, ';');
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Exportuje produkty do XLSX (PhpSpreadsheet)
     */
    public function exportToXlsx(int $userId, array $filters = []): string
    {
        // Vyžaduje PhpSpreadsheet
        // composer require phpoffice/phpspreadsheet
        
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \Exception('PhpSpreadsheet není nainstalovaný');
        }
        
        $products = $this->getProductsForExport($userId, $filters);
        $customFields = $this->getAllCustomFields($products);
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Hlavička
        $headers = array_merge(
            ['ID', 'Název', 'Kód', 'Cena s DPH', 'Kategorie', 'URL', 'Obrázek'],
            $customFields
        );
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Nastavit bold na hlavičku
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        
        // Data
        $row = 2;
        foreach ($products as $product) {
            $customData = json_decode($product['custom_data'] ?? '{}', true);
            
            $rowData = [
                $product['id'],
                $product['name'],
                $product['code'] ?? '',
                $product['price_vat'] ?? 0,
                $product['category'] ?? '',
                $product['url'] ?? '',
                $product['image_url'] ?? '',
            ];
            
            foreach ($customFields as $field) {
                $rowData[] = $customData[$field] ?? '';
            }
            
            $sheet->fromArray($rowData, null, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Vrať jako string
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer->save($tempFile);
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }
    
    /**
     * Získá produkty pro export
     */
    private function getProductsForExport(int $userId, array $filters = []): array
    {
        $sql = "SELECT * FROM products WHERE user_id = ?";
        $params = [$userId];
        
        // Filtry
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR code LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        // Custom field filtry - například weight > 1
        if (!empty($filters['custom'])) {
            foreach ($filters['custom'] as $field => $value) {
                $sql .= " AND JSON_EXTRACT(custom_data, '$." . $field . "') = ?";
                $params[] = $value;
            }
        }
        
        $sql .= " ORDER BY name LIMIT 10000"; // Max 10k pro export
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Získá všechny custom fields z produktů
     */
    private function getAllCustomFields(array $products): array
    {
        $allFields = [];
        
        foreach ($products as $product) {
            if (empty($product['custom_data'])) {
                continue;
            }
            
            $customData = json_decode($product['custom_data'], true);
            
            if (is_array($customData)) {
                $allFields = array_merge($allFields, array_keys($customData));
            }
        }
        
        return array_unique($allFields);
    }
}
