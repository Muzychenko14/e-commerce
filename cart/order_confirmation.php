<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: /progetto/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? null;
$stmt = $conn->prepare("
    SELECT o.id AS order_id, a.street, a.postal_code, a.country, p.payment_method, p.payment_status, p.created_at
    FROM orders o
    JOIN addresses a ON o.address_id = a.id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order_id) {
    die("ID заказа не указан.");
}





$items_stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.name, p.image
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();


if (!$order) {
    die("Заказ не найден.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order confirmation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body class="bg-gray-200 pt-[80px] min-h-screen flex flex-col">
    <?php include '../header.php'; ?>

    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl mx-auto">
            <div class="flex items-center mb-6 text-green-600">
                <i class="fas fa-check-circle text-3xl mr-3"></i>
                <h1 class="text-2xl font-bold">Your order has been confirmed!</h1>
            </div>

            <div class="space-y-4 text-gray-700 text-sm md:text-base">
                <div>
                    <span class="font-semibold"><i class="fas fa-receipt mr-2 text-blue-500"></i>Order number:</span>
                    <span class="ml-2"><?= htmlspecialchars($order['order_id']) ?></span>
                </div>

                <div>
                    <span class="font-semibold"><i class="fas fa-map-marker-alt mr-2 text-red-500"></i>Delivery address:</span>
                    <span class="ml-2"><?= htmlspecialchars($order['street']) ?>, <?= htmlspecialchars($order['postal_code']) ?>, <?= htmlspecialchars($order['country']) ?></span>
                </div>

                <div>
                    <span class="font-semibold"><i class="fas fa-credit-card mr-2 text-indigo-500"></i>Payment method:</span>
                    <span class="ml-2"><?= htmlspecialchars($order['payment_method']) ?></span>
                </div>

                <div>
                    <span class="font-semibold"><i class="fas fa-info-circle mr-2 text-yellow-500"></i>Payment status:</span>
                    <span class="ml-2"><?= htmlspecialchars($order['payment_status']) ?></span>
                </div>

                <div>
                    <span class="font-semibold"><i class="fas fa-calendar-alt mr-2 text-gray-500"></i>Order placement date:</span>
                    <span class="ml-2"><?= htmlspecialchars($order['created_at']) ?></span>
                </div>
                <div class="mt-6">
                    <h2 class="font-bold text-lg mb-2">🛍️ Products in this order:</h2>
                    <ul class="text-sm text-gray-700 space-y-3">
                        <?php foreach ($items as $item): ?>
                            <?php
                                $images = json_decode($item['image'], true);
                                $imgSrc = !empty($images) ? '/progetto/admin/uploads/' . htmlspecialchars($images[0]) : '/progetto/images/default.jpg';
                            ?>
                            <li class="flex items-center gap-3">
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-14 h-14 object-cover rounded border" />
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="text-sm text-gray-500">
                                        × <?= $item['quantity'] ?> — <?= number_format($item['price'] * $item['quantity'], 2) ?> $
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>


            </div>

            <div class="mt-8 text-center">
                <a href="/progetto/index.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition">
                    Return to home page
                </a>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>
</body>
</html>
