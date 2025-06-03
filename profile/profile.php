<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$addresses = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id");
$order_query = "
    SELECT o.*, p.name AS product_name, p.image AS product_image, p.id AS product_id
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = $user_id
    ORDER BY o.created_at DESC
";
$order_result = $conn->query($order_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile | SneakUP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-200 pt-[80px]">
<button onclick="history.back()" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
  </svg>
  Back
</button>
<?php include '../header.php'; ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-3xl font-bold">Profile</h2>
    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded">Quit from account</a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10 items-start">
    <div class="bg-white p-6 rounded-lg shadow-md">
      <div class="flex flex-col sm:flex-row items-center gap-6">
        <?php
        $avatarPath = (!empty($user['avatar'])) ? htmlspecialchars($user['avatar']) : "uploads/default.png";
        ?>
        <img id="userAvatar" src="/progetto/<?= $avatarPath ?>" alt="Avatar" class="w-24 h-24 rounded-full">

        <div>
          <div class="space-y-2">
            <div class="text-center sm:text-left">
              <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['username']) ?></h3>
              <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
            </div>

            <form id="avatarForm" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 items-center sm:items-start">
              <label for="avatarInput" class="cursor-pointer inline-block bg-gray-100 text-sm text-gray-700 px-4 py-2 rounded border border-gray-300 hover:bg-gray-200 transition">
                Choose photo
                <input type="file" name="avatar" id="avatarInput" class="hidden">
              </label>
              <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded">
                Update
              </button>
            </form>

          </div>

          <button id="toggleUserUpdate" class="bg-green-500 text-white px-4 py-2 rounded ">Edit profile</button>
        </div>
      </div>

      <div id="userUpdateWrapper" class="hidden mt-6">
        <form id="userUpdateForm" class="space-y-2">
          <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="border p-2 rounded w-full" placeholder="Username" required>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="border p-2 rounded w-full" placeholder="Email" required>
          <input type="password" name="new_password" class="border p-2 rounded w-full" placeholder="New password (optional)">
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded w-full">Update Info</button>
        </form>
      </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
      <h3 class="text-xl font-semibold mb-4">My Orders</h3>
      <?php if ($order_result->num_rows > 0): ?>
        <table class="w-full table-auto text-sm text-left">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-3 py-2">#</th>
              <th class="px-3 py-2">Item</th>
              <th class="px-3 py-2">Quantity</th>
              <th class="px-3 py-2">Sum</th>
              <th class="px-3 py-2">Status</th>
              <th class="px-3 py-2">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($order = $order_result->fetch_assoc()): ?>
              <tr class="border-b">
                <td class="px-3 py-2"><?= $order['id'] ?></td>
                <td class="px-3 py-2">
                  <a href="../product.php?id=<?= $order['product_id'] ?>" class="flex items-center space-x-2 hover:underline">
                    <?php
                      $imgs = json_decode($order['product_image'], true);
                      $thumb = !empty($imgs) ? '/progetto/admin/uploads/' . htmlspecialchars($imgs[0]) : 'default.jpg';
                    ?>
                    <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($order['product_name']) ?>" class="w-10 h-10 object-cover rounded">
                    <span><?= htmlspecialchars($order['product_name']) ?></span>
                  </a>
                </td>
                <td class="px-3 py-2"><?= $order['quantity'] ?></td>
                <td class="px-3 py-2"><?= number_format($order['total_price'], 2, ',', ' ') ?> €</td>
                <td class="px-3 py-2"><?= ucfirst($order['status']) ?></td>
                <td class="px-3 py-2"><?= date("d.m.Y", strtotime($order['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-gray-500">You have no orders yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Address Block (Full Width Below) -->
  <div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">My Addresses</h3>
      <button id="toggleAddressForm" class="text-blue-500 hover:text-blue-700 text-xl font-bold">+</button>
    </div>

    <div id="addressList" class="space-y-4">
      <?php if ($addresses->num_rows > 0): ?>
        <?php while ($row = $addresses->fetch_assoc()): ?>
          <form class="address-item flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b pb-2" data-id="<?= $row['id'] ?>">
            <input type="text" name="street" class="border p-1 rounded text-sm w-full sm:w-auto" value="<?= htmlspecialchars($row['street']) ?>" required>
            <input type="text" name="city" class="border p-1 rounded text-sm w-full sm:w-auto" value="<?= htmlspecialchars($row['city']) ?>" required>
            <input type="text" name="postal_code" class="border p-1 rounded text-sm w-full sm:w-auto" value="<?= htmlspecialchars($row['postal_code']) ?>" required>
            <input type="text" name="country" class="border p-1 rounded text-sm w-full sm:w-auto" value="<?= htmlspecialchars($row['country']) ?>" required>
            <div class="flex gap-2 mt-2 sm:mt-0">
              <button type="button" class="update-address bg-green-500 text-white px-2 py-1 rounded text-sm">Save</button>
              <button type="button" class="delete-address text-red-500 text-sm" data-id="<?= $row['id'] ?>">Delete</button>
            </div>
          </form>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-gray-500">You have not added any addresses yet.</p>
      <?php endif; ?>
    </div>

    <form id="addAddressForm" class="mt-6 space-y-2 hidden">
      <h4 class="text-lg font-medium">New address</h4>
      <input type="text" name="street" placeholder="Street, building" required class="border p-2 rounded w-full">
      <input type="text" name="city" placeholder="City" required class="border p-2 rounded w-full">
      <input type="text" name="postal_code" placeholder="Postal code" required class="border p-2 rounded w-full">
      <input type="text" name="country" placeholder="Country" required class="border p-2 rounded w-full">
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full">Add address</button>
    </form>
  </div>
</div>

<?php include '../footer.php'; ?>
<script src="profile.js"></script>
</body>
</html>
<?php $conn->close(); ?>
