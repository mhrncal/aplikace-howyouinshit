<?php
require_once __DIR__ . '/bootstrap.php';

$auth->logout();
flash('success', 'Byli jste úspěšně odhlášeni');
redirect('/login.php');
