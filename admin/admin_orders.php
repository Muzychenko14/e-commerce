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
    echo "<p>Order #$order_id updated to '$status'</p>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_order_id"])) {
    $order_id = intval($_POST["delete_order_id"]);
    $conn->query("DELETE FROM orders WHERE id='$order_id'");
    echo "<p>Order #$order_id deleted.</p>";
}

$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$date_filter = isset($_GET['date']) ? $_GET['date'] : null;

$query = "SELECT orders.id, users.username, orders.user_id, products.name, orders.quantity, orders.total_price, orders.status, orders.created_at 
          FROM orders 
          JOIN products ON orders.product_id = products.id 
          JOIN users ON orders.user_id = users.id
          WHERE 1=1";

if ($user_filter) {
    $query .= " AND orders.user_id = '$user_filter'";
}
if($date_filter){
    $query .= " AND DATE(orders.created_at) = '$date_filter'";
}

$query .= " ORDER BY orders.created_at DESC";
$result = $conn->query($query);
?>

<h2>All Orders</h2>

<form method="get" style="margin-bottom: 20px;">
    <input type="number" name="user_id" placeholder="User ID" value="<?= htmlspecialchars($user_filter) ?>">
    <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
    <button type="submit">Search</button>
</form>

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<form method='post' style='display: inline-block; margin-bottom: 10px;'>
                <p>
                    Order #{$row['id']} - User: {$row['username']} (ID: {$row['user_id']}) - {$row['name']} ({$row['quantity']} pcs.) - {$row['total_price']} USD - Date: {$row['created_at']}
                    <select name='status'>
                        <option value='pending' " . ($row['status'] == 'pending' ? "selected" : "") . ">Pending..</option>
                        <option value='shipped' " . ($row['status'] == 'shipped' ? "selected" : "") . ">Shipped!</option>
                        <option value='delivered' " . ($row['status'] == 'delivered' ? "selected" : "") . ">Delivered!</option>
                    </select>
                    <input type='hidden' name='order_id' value='{$row['id']}'>
                    <button type='submit'>Update</button>
                </p>
              </form>

              <form method='post' style='display: inline-block;'>
                <input type='hidden' name='delete_order_id' value='{$row['id']}'>
                <button type='submit' style='color: red;'>Deleted!</button>
              </form>";

        echo"<hr>";
    }
} else {
    echo "<p>No orders found.</p>";
}

$conn->close();
?>
