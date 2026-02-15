<?php
// Redirect na novou lokaci  
header('Location: /pages/auth/reset-password.php?token=' . ($_GET['token'] ?? ''));
exit;
