<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\User;
use App\Core\Security;

$userModel = new User();
$userId = $auth->userId();

echo "<h1>Profile Update Debug</h1>";

if (isPost()) {
    echo "<h2>POST Data přijata:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $action = post('action');
    echo "<h3>Action: {$action}</h3>";
    
    if ($action === 'update_profile') {
        $data = [
            'name' => post('name'),
            'email' => post('email'),
            'company_name' => post('company_name'),
            'ico' => post('ico'),
            'dic' => post('dic'),
            'phone' => post('phone'),
            'address' => post('address'),
            'city' => post('city'),
            'zip' => post('zip'),
            'country' => post('country', 'Česká republika'),
        ];
        
        echo "<h2>Data pro update:</h2>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        
        echo "<h2>Volání userModel->update({$userId}, data)...</h2>";
        
        $result = $userModel->update($userId, $data);
        
        echo "<h3>Výsledek: " . ($result ? 'TRUE ✅' : 'FALSE ❌') . "</h3>";
        
        if (!$result) {
            echo "<h3>Errors:</h3>";
            echo "<pre>";
            print_r(getErrors());
            echo "</pre>";
        }
        
        // Načti uživatele z DB
        echo "<h2>Uživatel z DB po update:</h2>";
        $user = $userModel->findById($userId);
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    }
} else {
    // Formulář
    $user = $userModel->findById($userId);
    ?>
    <h2>Test formulář:</h2>
    <form method="POST">
        <?= csrf() ?>
        <input type="hidden" name="action" value="update_profile">
        
        <p><strong>Jméno:</strong><br>
        <input type="text" name="name" value="<?= e($user['name']) ?>" size="40"></p>
        
        <p><strong>Email:</strong><br>
        <input type="email" name="email" value="<?= e($user['email']) ?>" size="40"></p>
        
        <p><strong>Firma:</strong><br>
        <input type="text" name="company_name" value="<?= e($user['company_name'] ?? '') ?>" size="40"></p>
        
        <p><strong>IČO:</strong><br>
        <input type="text" name="ico" value="<?= e($user['ico'] ?? '') ?>" size="40"></p>
        
        <p><strong>DIČ:</strong><br>
        <input type="text" name="dic" value="<?= e($user['dic'] ?? '') ?>" size="40"></p>
        
        <p><strong>Telefon:</strong><br>
        <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" size="40"></p>
        
        <p><strong>Adresa:</strong><br>
        <input type="text" name="address" value="<?= e($user['address'] ?? '') ?>" size="40"></p>
        
        <p><strong>Město:</strong><br>
        <input type="text" name="city" value="<?= e($user['city'] ?? '') ?>" size="40"></p>
        
        <p><strong>PSČ:</strong><br>
        <input type="text" name="zip" value="<?= e($user['zip'] ?? '') ?>" size="40"></p>
        
        <p><strong>Země:</strong><br>
        <input type="text" name="country" value="<?= e($user['country'] ?? 'Česká republika') ?>" size="40"></p>
        
        <button type="submit">ULOŽIT</button>
    </form>
    
    <hr>
    <h2>Aktuální data z DB:</h2>
    <pre><?php print_r($user); ?></pre>
    <?php
}
