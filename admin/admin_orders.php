<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION["admin"]) || $_SESSION["admin"] !== true) {
    die("Access denied!");
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["order_id"], $_POST["status"])) {
    $order_id = intval($_POST["order_id"]);
    $status = $_POST["status"];
    $conn->query("UPDATE orders SET status='$status' WHERE id='$order_id'");
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_order_id"])) {
    $order_id = intval($_POST["delete_order_id"]);
    $conn->query("DELETE FROM orders WHERE id='$order_id'");
}


$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$date_filter = isset($_GET['date']) ? $_GET['date'] : null;


$query = "
    SELECT 
        orders.id, 
        users.username, 
        orders.user_id, 
        products.name AS product_name, 
        order_items.quantity, 
        orders.total_price, 
        orders.status, 
        orders.created_at,
        payments.payment_method,
        products.image AS product_image,
        products.id AS product_id
    FROM orders 
    JOIN order_items ON orders.id = order_items.order_id
    JOIN products ON order_items.product_id = products.id 
    JOIN users ON orders.user_id = users.id
    LEFT JOIN payments ON payments.order_id = orders.id
    WHERE 1=1
";
$user_search = $_GET['user_search'] ?? null;

if ($user_search !== null && $user_search !== '') {
    $escaped_search = $conn->real_escape_string($user_search);
    if (is_numeric($escaped_search)) {
        $query .= " AND users.id = '$escaped_search'";
    } else {
        $query .= " AND users.username LIKE '%$escaped_search%'";
    }
}


if ($user_filter) {
    $query .= " AND orders.user_id = '$user_filter'";
}
if ($date_filter) {
    $query .= " AND DATE(orders.created_at) = '$date_filter'";
}

$query .= " ORDER BY orders.created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

</head>
<?php include '../header.php'; ?>
<body class="bg-gray-100 p-6 pt-[80px]">
    <div class="p-4">
        <a href="admin.php" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>


    <h1 class="text-3xl font-bold mb-6">Orders</h1>

    <form method="get" class="mb-6 flex flex-wrap gap-4 items-center">
        <input type="text" name="user_search" placeholder="User ID or Name" value="<?= htmlspecialchars($_GET['user_search'] ?? '') ?>" class="p-2 border rounded w-52">
        <input type="text" id="datePicker" name="date" placeholder="Date" value="<?= htmlspecialchars($date_filter) ?>" class="p-2 border rounded w-40">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Search</button>
    </form>


    <div class="bg-white p-6 rounded shadow overflow-x-auto">
        <table class="min-w-full table-auto text-sm text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Product</th>
                    <th class="px-4 py-2">Qty</th>
                    <th class="px-4 py-2">Sum</th>
                    <th class="px-4 py-2">Payment</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($order = $result->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?= $order['id'] ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($order['username']) ?></td>
                            <td class="px-4 py-2">
                                <a href="/progetto/product.php?id=<?= $order['product_id'] ?>" class="flex items-center gap-2 hover:underline">
                                    <?php
                                        $imgs = json_decode($order['product_image'], true);
                                        $thumb = !empty($imgs) ? '/progetto/admin/uploads/' . htmlspecialchars($imgs[0]) : 'default.jpg';
                                    ?>
                                    <img src="<?= $thumb ?>" class="w-10 h-10 object-cover rounded" alt="">
                                    <span><?= htmlspecialchars($order['product_name']) ?></span>
                                </a>
                            </td>
                            <td class="px-4 py-2"><?= $order['quantity'] ?></td>
                            <td class="px-4 py-2"><?= number_format($order['total_price'], 2, ',', ' ') ?> €</td>
                            <td class="px-4 py-2"><?= htmlspecialchars($order['payment_method'] ?? '—') ?></td>
                            <td class="px-4 py-2"><?= date("Y-m-d H:i", strtotime($order['created_at'])) ?></td>
                            <td class="px-4 py-2">
                                <form method="post" class="flex items-center gap-2">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" class="border rounded p-1">
                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    </select>
                                    <button type="submit" class="text-blue-600 hover:underline">Update</button>
                                </form>
                            </td>
                            <td class="px-4 py-2">
                                <form method="post" onsubmit="return confirm('Delete this order?')">
                                    <input type="hidden" name="delete_order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-4 py-4 text-center text-gray-500">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include '../footer.php'; ?>
    <script src="admin.js"></script>
</body>
</html>

<?php $conn->close(); ?>
