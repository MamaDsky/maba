<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($action == 'add') {
        $product_id = intval($_POST['product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += 1;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
        echo json_encode(['status' => 'success', 'total_items' => array_sum($_SESSION['cart'])]);
        exit;
    }

    if ($action == 'clear') {
        $_SESSION['cart'] = [];
        header("Location: checkout.php");
        exit;
    }
}