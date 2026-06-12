<?php
session_start();
require_once '../includes/config.php';
if (isset($_SESSION['admin_id'])) logAction('Logout');
session_destroy();
header('Location: login.php');
exit;
?>
