<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION["user_id"])) {
    header("Location: /progetto/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$cart_items = [];
$cart_query = $conn->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image 
                              FROM cart c 
                              JOIN products p ON c.product_id = p.id 
                              WHERE c.user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$result = $cart_query->get_result();

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}
$cart_query->close();

$product_ids = array_keys($cart_items);
$products = [];

if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
}
$addresses = [];
$addr_result = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$addr_result->bind_param("i", $user_id);
$addr_result->execute();
$addr_data = $addr_result->get_result();
while ($row = $addr_data->fetch_assoc()) {
    $addresses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placing an order</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .step { display: none; }
        .step.active { display: block; }
    </style>
</head>
<body class="bg-gray-200 pt-[80px]">
<?php include '../header.php'; ?>

<div class="container mx-auto flex flex-col lg:flex-row gap-6 p-4">

    <!-- Левая часть: шаги -->
    <form action="place_order.php" method="POST" onsubmit="return validateForm()" class="lg:w-2/3 w-full space-y-6 bg-white p-6 rounded shadow">

        <div class="flex items-center mb-6">
            <div class="w-full bg-gray-200 h-2 rounded">
                <div id="progress-bar" class="bg-green-500 h-2 rounded transition-all duration-300" style="width: 50%;"></div>
            </div>
            <div class="ml-4 text-sm text-gray-600" id="progress-text">Step 1 of 2</div>
        </div>

        <!-- Шаг 1: Адрес -->
        <div class="step active" id="step-1">
            <h2 class="text-lg font-bold mb-4">📍 Delivery address</h2>
            <label class="block mb-2"><input type="radio" name="address_type" value="new" checked> New address</label>
            <label class="block mb-4"><input type="radio" name="address_type" value="saved"> Saved address</label>

            <div id="new-address-fields">
                <input type="text" name="street" placeholder="Street" class="border p-2 w-full mb-2">
                <input type="text" name="postal_code" placeholder="Postal code" class="border p-2 w-full mb-2">
                <input type="text" name="country" placeholder="Country" class="border p-2 w-full mb-2">
            </div>

            <div id="saved-address-fields" class="hidden">
                <?php if (!empty($addresses)): ?>
                    <select name="saved_address_id" class="border p-2 w-full">
                        <option value="">Choose address</option>
                        <?php foreach ($addresses as $addr): ?>
                            <option value="<?= $addr['id'] ?>">
                                <?= htmlspecialchars($addr['street']) ?>, <?= $addr['postal_code'] ?>, <?= $addr['country'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <p class="text-gray-500">There are no saved addresses.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Шаг 2: Оплата -->
        <div class="step" id="step-2">
            <h2 class="text-lg font-bold mb-4">💳 Payment method</h2>
            <select name="payment_method" id="payment-method" class="border p-2 w-full mb-4" required>
                <option value="" disabled selected>Choose payment method</option>
                <option value="card">Card</option>
                <option value="paypal">PayPal</option>
                <option value="cash">Cash</option>
            </select>

            <div id="card-fields" class="hidden">
                <input type="text" id="card_number" name="card_number" class="border p-2 w-full mb-2" placeholder="Card number" maxlength="19">
                <div class="flex gap-2">
                    <input type="text" id="card_expiry" name="card_expiry" class="border p-2 w-full" placeholder="MM/YY" maxlength="5">
                    <input type="text" name="card_cvc" class="border p-2 w-full" placeholder="CVC" maxlength="4">
                </div>
                <input type="text" name="card_holder" placeholder="Card holder name" class="border p-2 w-full mb-2">
            </div>


            <div id="paypal-fields" class="hidden mt-2">
                <input type="email" name="paypal_email" class="border p-2 w-full" placeholder="Email PayPal">
                <input type="text" name="paypal_owner" placeholder="Account holder name" class="border p-2 w-full mb-2">

            </div>

            <button type="submit" class="mt-6 bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 w-full">Checkout ✅</button>
        </div>

        <div class="flex justify-between mt-4">
            <button type="button" onclick="prevStep()" class="bg-gray-500 text-white px-4 py-2 rounded">Back</button>
            <button type="button" onclick="nextStep()" id="next-btn" class="bg-green-600 text-white px-4 py-2 rounded">Next</button>
        </div>
    </form>

    
    <div class="lg:w-1/3 w-full bg-white p-4 rounded shadow">
        <h2 class="text-xl font-bold mb-4">🛒 Your order</h2>
        <?php if (!empty($cart_items)): ?>
            <?php $total = 0; ?>
            <?php foreach ($cart_items as $item): ?>
                <?php
                    $qty = $item['quantity'];
                    $subtotal = $item['price'] * $qty;
                    $total += $subtotal;
                    $images = json_decode($item['image'], true);
                    $img = !empty($images) ? '/progetto/admin/uploads/' . htmlspecialchars($images[0]) : 'default.jpg';
                ?>
                <div class="flex items-center mb-3">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-16 h-16 object-cover rounded mr-4">
                    <div>
                        <h3 class="text-sm font-bold"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="text-xs text-gray-600">x<?= $qty ?> — <?= number_format($subtotal, 2) ?> $</p>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="mt-4 font-bold flex justify-between text-lg">
                <span>Total:</span>
                <span><?= number_format($total, 2) ?> $</span>
            </div>
        <?php else: ?>
            <p class="text-gray-500">Cart is empty.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>
<script src="checkout.js"></script>

</body>
</html>
