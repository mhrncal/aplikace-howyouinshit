<?php

namespace App\Modules\Products\Controllers;

use App\Core\Module;
use App\Modules\Products\Models\Product;

class ProductController extends Module
{
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }

    /**
     * Seznam produktů
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $page = (int) get('page', 1);
        $search = get('search', '');
        $userId = $this->auth->isSuperAdmin() ? null : $this->auth->userId();
        
        if (!empty($search)) {
            $products = $this->productModel->search($this->auth->userId(), $search);
            $pagination = null;
        } else {
            $data = $this->productModel->getAll($userId ?? 0, $page);
            $products = $data['products'];
            $pagination = $data['pagination'];
        }
        
        $this->render('products/index', [
            'title' => 'Produkty',
            'products' => $products,
            'pagination' => $pagination,
            'search' => $search
        ]);
    }

    /**
     * Detail produktu
     */
    public function detail(): void
    {
        $this->requireAuth();
        
        $id = (int) get('id');
        $userId = $this->auth->isSuperAdmin() ? null : $this->auth->userId();
        
        $product = $this->productModel->findById($id, $userId ?? 0);
        
        if (!$product) {
            flash('error', 'Produkt nebyl nalezen');
            redirect('/app/products/');
        }
        
        // Načtení variant
        $variants = $this->productModel->getVariants($id);
        
        $this->render('products/detail', [
            'title' => $product['name'],
            'product' => $product,
            'variants' => $variants
        ]);
    }

    /**
     * Smazání produktu
     */
    public function delete(): void
    {
        $this->requireAuth();
        
        if (!$this->validatePost()) {
            flash('error', 'Neplatný požadavek');
            redirect('/products.php');
        }
        
        $id = (int) post('product_id');
        $userId = $this->auth->userId();
        
        if ($this->productModel->delete($id, $userId)) {
            flash('success', 'Produkt byl smazán');
        } else {
            flash('error', 'Nepodařilo se smazat produkt');
        }
        
        redirect('/products.php');
    }

    /**
     * Export produktů do CSV
     */
    public function export(): void
    {
        $this->requireAuth();
        
        $userId = $this->auth->isSuperAdmin() ? null : $this->auth->userId();
        $products = $this->productModel->getAllForExport($userId ?? 0);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="produkty_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM pro UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, [
            'ID', 'Kód', 'EAN', 'Název', 'Kategorie', 'Nákupní cena', 
            'Prodejní cena', 'Sklad', 'Status dostupnosti'
        ], ';');
        
        // Data
        foreach ($products as $product) {
            fputcsv($output, [
                $product['id'],
                $product['code'] ?? '',
                $product['ean'] ?? '',
                $product['name'],
                $product['category'] ?? '',
                $product['purchase_price'] ?? '',
                $product['standard_price'] ?? '',
                $product['stock'],
                $product['availability_status'] ?? ''
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}
