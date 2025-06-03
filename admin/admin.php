<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION["admin"])) {
    header("Location: ../login.php");
    exit;
}

$order_result = $conn->query("
    SELECT 
        o.id, 
        o.user_id, 
        o.address_id, 
        o.product_id,
        o.quantity, 
        o.total_price, 
        o.status, 
        o.created_at,
        o.payment_method,
        p.name AS product_name,
        u.username
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");



$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM orders")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_admins = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 1")->fetch_assoc()['total'];
$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$max_order = $conn->query("SELECT MAX(total_price) as max_total FROM orders")->fetch_assoc()['max_total'];


$category_query = "SELECT DISTINCT category FROM products";
$category_result = $conn->query($category_query);
$categories = [];

if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <style>
    .scrollbar-thin {
        scrollbar-width: thin;
    }
    .scrollbar-thumb-gray-400 {
        scrollbar-color: #9ca3af #f3f4f6;
    }
    </style>

</head>

<?php include '../header.php'; ?>

<body class="bg-gray-200 pt-[80px]">
    <div class="p-4">
    <button onclick="history.back()" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </button>
    </div>


    <div class="container mx-auto p-6 pt-[80px] px-4">
         <h1 class="text-3xl font-bold mb-6 text-center">Admin Dashboard</h1>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 max-w-4xl mx-auto">
        
            <div class="bg-white p-4 rounded shadow text-center ">
                <h3 class="text-sm font-semibold text-gray-500">Total orders</h3>
                <p class="text-2xl font-bold text-gray-800"><?= $total_orders ?></p>
            </div>
            <div class="bg-white p-4 rounded shadow text-center">
                <h3 class="text-sm font-semibold text-gray-500">Total revenue</h3>
                <p class="text-2xl font-bold text-green-600"><?= number_format($total_revenue, 2, ',', ' ') ?> €</p>
            </div>
            <div class="bg-white p-4 rounded shadow text-center">
                <h3 class="text-sm font-semibold text-gray-500">Users</h3>
                <p class="text-2xl font-bold text-gray-800"><?= $total_users ?></p>
            </div>

            <div class="bg-white p-4 rounded shadow text-center">
                <h3 class="text-sm font-semibold text-gray-500">Admins</h3>
                <p class="text-2xl font-bold text-blue-600"><?= $total_admins ?></p>
            </div>

            <div class="bg-white p-4 rounded shadow text-center">
                <h3 class="text-sm font-semibold text-gray-500">Products</h3>
                <p class="text-2xl font-bold text-indigo-600"><?= $total_products ?></p>
            </div>

            <div class="bg-white p-4 rounded shadow text-center">
                <h3 class="text-sm font-semibold text-gray-500">Max Order</h3>
                <p class="text-2xl font-bold text-red-600"><?= number_format($max_order, 2, ',', ' ') ?> €</p>
            </div>
        </div>

        


                <div class="flex flex-col lg:flex-row gap-6">
                    <div class="bg-white p-6 shadow-md rounded-lg max-w-xl mx-auto">
                        <h2 class="text-2xl font-semibold mb-4">Add Item</h2>
                        <form id="addProductForm" enctype="multipart/form-data" method="POST">
                            <input type="text" name="name" placeholder="Product Name" class="w-full p-2 border rounded mb-2" required>
                            <textarea name="description" placeholder="Description" class="w-full p-2 border rounded mb-2" required></textarea>
                            <input type="number" name="price" placeholder="Price" class="w-full p-2 border rounded mb-2" required>

                            <select name="category" class="w-full p-2 border rounded mb-2" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                <?php endforeach; ?>
                            </select>


                            <div id="dropzone" class="border-2 border-dashed border-gray-400 p-4 rounded-lg text-center cursor-pointer mb-2">
                                <p class="text-gray-600">Drag and drop images here or click to select</p>
                                <input type="file" id="imageInput" name="image[]" multiple accept="image/*" class="hidden" />
                            </div>

                            <div id="imagePreview" class="flex flex-wrap gap-2 mb-2"></div>
                            <div id="uploadProgress" class="text-sm text-gray-500 mb-2"></div>

                            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Add Product</button>
                        </form>
                    </div>

                    <div class="bg-white p-6 shadow-md rounded-lg mt-6 overflow-x-auto ">
                        <h2 class="text-2xl font-semibold mb-4">Orders</h2>
                        <table class="min-w-full table-auto text-sm text-left">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2">#</th>
                                    <th class="px-4 py-2">User</th>
                                    <th class="px-4 py-2">Item</th>
                                    <th class="px-4 py-2">Quantity</th>
                                    <th class="px-4 py-2">Sum</th>
                                    <th class="px-4 py-2">Payment</th>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2">Status</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $order_result->fetch_assoc()): ?>
                                <<tr class="border-b">
                                    <td class="px-4 py-2"><?= $order['id'] ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($order['username']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($order['product_name']) ?></td>
                                    <td class="px-4 py-2"><?= $order['quantity'] ?></td>
                                    <td class="px-4 py-2"><?= number_format($order['total_price'], 2, ',', ' ') ?> €</td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($order['payment_method']) ?></td>
                                    <td class="px-4 py-2"><?= date("Y-m-d H:i", strtotime($order['created_at'])) ?></td>
                                    <td class="px-4 py-2"><?= ucfirst($order['status']) ?></td>
                                </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <div class="overflow-x-auto">
                <h2 class="text-2xl font-semibold mb-4">Item List</h2>
                <button onclick="loadProducts()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Refresh List</button>
                <div id="product-list" class="flex overflow-x-auto space-x-4 p-4 snap-x snap-mandatory"></div>
            </div>


        </div>

    </div>


    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Edit Product</h2>
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editId">
            <input type="text" id="editName" name="name" required>
            <textarea id="editDescription" name="description" required></textarea>
            <input type="number" id="editPrice" name="price" required>

            <label class="block text-sm font-medium mt-2">Change img</label>
            <div id="currentImages" class="flex flex-wrap gap-2 mb-2"></div>
            <input type="file" id="editImages" name="image[]" multiple class="mt-1 p-2 border rounded w-full">

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-400 text-white rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
            </div>
        </form>

    </div>
    </div>


    <div id="toast" class="hidden fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-white z-50 opacity-0 transition-opacity duration-300"></div>

    <?php include '../footer.php'; ?>
    <script src="admin.js?v=123"></script>

</body>
</html> 
