
<?php
session_start();
require_once "../config/database.php";

$cart_item_id = filter_input(
    INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT
);

if (!$cart_item_id) {
    die("Invalid cart item.");
}

$stmt = $pdo->prepare("
    DELETE FROM cart_items
    WHERE cart_item_id = ?
    AND cart_id = ?
");

$stmt->execute([
    $cart_item_id,
    $_SESSION['cart_id'] ?? 0
]);

header("Location: index.php");
exit;
