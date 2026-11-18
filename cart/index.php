
<?php
session_start();
require_once "../config/database.php";

// Temporary user ID for testing.
// Replace this with your logged-in user ID later.
$user_id = 1;

// Create cart if it does not exist
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

$_SESSION['cart_id'] = $cart_id;

// Get products
$products = $pdo->query(
    "SELECT * FROM products ORDER BY product_id DESC"
)->fetchAll();

// Get cart items
$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.quantity,
           p.product_name, p.price, p.stock, p.unit,
           (ci.quantity * p.price) AS subtotal
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart - Mighty Business</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Mighty Business</h1>
    <h2>Shopping Cart Management</h2>

    <h3>Available Products</h3>

    <div class="products">

        <?php foreach ($products as $product): ?>

        <div class="product-card">

            <h3>
                <?= htmlspecialchars($product['product_name']) ?>
            </h3>

            <p>
                Price: ₱<?= number_format($product['price'], 2) ?>
            </p>

            <p>
                Unit: <?= htmlspecialchars($product['unit']) ?>
            </p>

            <p>
                Available Stock: <?= $product['stock'] ?>
            </p>

            <form action="add_to_cart.php" method="POST">

                <input type="hidden"
                       name="product_id"
                       value="<?= $product['product_id'] ?>">

                <input type="number"
                       name="quantity"
                       value="1"
                       min="1"
                       max="<?= $product['stock'] ?>"
                       required>

                <button type="submit">
                    Add to Cart
                </button>

            </form>

        </div>

        <?php endforeach; ?>

    </div>

    <hr>

    <h2>Your Shopping Cart</h2>

    <?php if (count($cart_items) > 0): ?>

    <table>

        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($cart_items as $item): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($item['product_name']) ?>
                </td>

                <td>
                    ₱<?= number_format($item['price'], 2) ?>
                </td>

                <td>

                    <form action="update_cart.php"
                          method="POST">

                        <input type="hidden"
                               name="cart_item_id"
                               value="<?= $item['cart_item_id'] ?>">

                        <input type="number"
                               name="quantity"
                               value="<?= $item['quantity'] ?>"
                               min="1"
                               max="<?= $item['stock'] ?>"
                               required>

                        <button type="submit">
                            Update
                        </button>

                    </form>

                </td>

                <td>
                    ₱<?= number_format($item['subtotal'], 2) ?>
                </td>

                <td>

                    <form action="remove_from_cart.php"
                          method="POST">

                        <input type="hidden"
                               name="cart_item_id"
                               value="<?= $item['cart_item_id'] ?>">

                        <button type="submit"
                                class="remove-btn">
                            Remove
                        </button>

                    </form>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <h2>
        Total Amount: ₱<?= number_format($total, 2) ?>
    </h2>

    <?php else: ?>

        <p>Your cart is currently empty.</p>

    <?php endif; ?>

</div>

</body>
</html>
