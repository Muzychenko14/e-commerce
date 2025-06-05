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
        oi.product_id,
        oi.quantity, 
        o.total_price, 
        o.status, 
        o.created_at,
        pay.payment_method,
        u.username,
        p.name AS product_name,
        p.image AS product_image
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN payments pay ON o.id = pay.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
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
        
            <a href="admin_orders.php" class="block bg-white p-4 rounded shadow text-center hover:bg-gray-100 transition">
                <h3 class="text-sm font-semibold text-gray-500">Total orders</h3>
                <p class="text-2xl font-bold text-gray-800"><?= $total_orders ?></p>
            </a>
            <div class="bg-white p-4 rounded shadow text-center">
                <h3 class="text-sm font-semibold text-gray-500">Total revenue</h3>
                <p class="text-2xl font-bold text-green-600"><?= number_format($total_revenue, 2, ',', ' ') ?> $</p>
            </div>
            <a href="admin_user_panel.php" class="block bg-white p-4 rounded shadow text-center hover:bg-gray-100 transition">
                <h3 class="text-sm font-semibold text-gray-500">Users</h3>
                <p class="text-2xl font-bold text-gray-800"><?= $total_users ?></p>
            </a>

            <a href="#item" class="block bg-white p-4 rounded shadow text-center hover:bg-gray-100 transition">
                <h3 class="text-sm font-semibold text-gray-500">Products</h3>
                <p class="text-2xl font-bold text-indigo-600"><?= $total_products ?></p>
            </a>
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
                        <h2 class="text-2xl font-semibold mb-4">Latest Orders</h2>
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
                                    <th class="px-4 py-2">Action</th>


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
                                    <td class="px-4 py-2">
                                        <a href="/progetto/product.php?id=<?= $order['product_id'] ?>" class="flex items-center space-x-2 hover:underline">
                                            <?php
                                            $imgs = json_decode($order['product_image'], true);
                                            $thumb = !empty($imgs) ? '/progetto/admin/uploads/' . htmlspecialchars($imgs[0]) : 'default.jpg';
                                            ?>
                                            <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($order['product_name']) ?>" class="w-10 h-10 object-cover rounded">
                                            <span><?= htmlspecialchars($order['product_name']) ?></span>
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">
                                        <button onclick="deleteOrder(<?= $order['id'] ?>)" class="text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <div id="item">
                <h2 class="text-2xl font-semibold mb-4">Item List</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php
                $products = $conn->query("SELECT id, name, image FROM products ORDER BY id DESC");
                while ($p = $products->fetch_assoc()):
                    $imgs = json_decode($p['image'], true);
                    $thumb = !empty($imgs) ? 'uploads/' . htmlspecialchars($imgs[0]) : 'default.jpg';
                ?>
                    <div class="relative group">
                        <a href="admin_item.php?id=<?= $p['id'] ?>" class="block bg-white rounded-lg shadow hover:shadow-md transition">
                            <img src="<?= $thumb ?>" class="w-full h-48 object-cover rounded-t-lg" alt="<?= htmlspecialchars($p['name']) ?>">
                            <div class="p-3">
                                <h3 class="text-lg font-semibold"><?= htmlspecialchars($p['name']) ?></h3>
                            </div>
                        </a>
                        <button
                            class="absolute top-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition"
                            onclick="deleteProduct(<?= $p['id'] ?>)">
                            Delete
                        </button>
                    </div>
                <?php endwhile; ?>
                </div>
            </div>

            </div>


        </div>

    </div>


    <div id="editModal" class="overflow-y-auto fixed inset-0 bg-black/50 z-50 hidden items-center justify-center transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl mx-4 p-6 relative animate-fade-in">
            
            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-black transition text-2xl">&times;</button>

            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Edit Product</h2>

            <form id="editForm" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" id="editId">

            <div>
                <label for="editName" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                <input type="text" id="editName" name="name" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label for="editDescription" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="editDescription" name="description" required rows="3" class="w-full p-3 border border-gray-300 rounded-lg resize-none focus:ring focus:ring-blue-300"></textarea>
            </div>

            <div>
                <label for="editPrice" class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                <input type="number" id="editPrice" name="price" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Images</label>
                <div id="currentImages" class="flex flex-wrap gap-3"></div>
            </div>

            <div>
                <label for="editImages" class="block text-sm font-medium text-gray-700 mb-1">Add More Images</label>
                <input type="file" id="editImages" name="image[]" multiple class="w-full border p-2 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-5 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-semibold">Save</button>
            </div>
            </form>
        </div>
        </div>



    <div id="toast" class="hidden fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-white z-50 opacity-0 transition-opacity duration-300"></div>

    <?php include '../footer.php'; ?>
    <script src="admin.js?v=123"></script>

</body>
</html> 
