<?php
require_once __DIR__ . '/bootstrap.php';

if ($auth->check()) {
    redirect('/dashboard.php');
} else {
    redirect('/login.php');
}
