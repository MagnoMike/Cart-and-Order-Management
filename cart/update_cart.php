
<?php
session_start();
require_once "../config/database.php";

$cart_item_id = filter_input(
    INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST, 'quantity', FILTER_VALIDATE_INT
);

if (!$cart_item_id || !$quantity || $quantity < 1) {
    die("Invalid quantity.");
}

$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.cart_id,
           ci.product_id, p.stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.cart_item_id = ? AND ci.cart_id = ?
");

$stmt->execute([
    $cart_item_id,
    $_SESSION['cart_id'] ?? 0
]);

$item = $stmt->fetch();

if (!$item) {
    die("Cart item not found.");
}

if ($quantity > $item['stock']) {
    die("Quantity exceeds available stock.");
}

$stmt = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE cart_item_id = ? AND cart_id = ?
");

$stmt->execute([
    $quantity,
    $cart_item_id,
    $item['cart_id']
]);

header("Location: index.php");
exit;
