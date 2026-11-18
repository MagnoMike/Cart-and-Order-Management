
<?php
session_start();
require_once "../config/database.php";

$user_id = 1; // Temporary testing ID

$product_id = filter_input(
    INPUT_POST, 'product_id', FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST, 'quantity', FILTER_VALIDATE_INT
);

if (!$product_id || !$quantity || $quantity < 1) {
    die("Invalid product or quantity.");
}

try {
    $pdo->beginTransaction();

    // Validate product and stock
    $stmt = $pdo->prepare("
        SELECT stock FROM products
        WHERE product_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception("Product not found.");
    }

    // Check existing cart
    $stmt = $pdo->prepare(
        "SELECT cart_id FROM carts WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $pdo->prepare(
            "INSERT INTO carts (user_id) VALUES (?)"
        );
        $stmt->execute([$user_id]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['cart_id'];
    }

    // Check existing item quantity
    $stmt = $pdo->prepare("
        SELECT quantity FROM cart_items
        WHERE cart_id = ? AND product_id = ?
    ");
    $stmt->execute([$cart_id, $product_id]);
    $existing = $stmt->fetch();

    $new_quantity = $quantity +
        ($existing ? $existing['quantity'] : 0);

    if ($new_quantity > $product['stock']) {
        throw new Exception("Insufficient stock.");
    }

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([
            $new_quantity, $cart_id, $product_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO cart_items
            (cart_id, product_id, quantity)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $cart_id, $product_id, $quantity
        ]);
    }

    $pdo->commit();

    header("Location: index.php");
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(htmlspecialchars($e->getMessage()));
}
