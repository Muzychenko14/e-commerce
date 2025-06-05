<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION["admin"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Product ID missing");
}

$id = (int)$_GET['id'];
$product_query = $conn->prepare("SELECT * FROM products WHERE id = ?");
$product_query->bind_param("i", $id);
$product_query->execute();
$product_result = $product_query->get_result();

if ($product_result->num_rows === 0) {
    die("Product not found");
}

$product = $product_result->fetch_assoc();
$images = json_decode($product['image'], true) ?? [];

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
    <title>Edit Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 py-10 px-4 pt-[80px]">
    <div class="p-4">
        <a href="admin.php" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>
    <div class="max-w-2xl mx-auto bg-white shadow-md rounded p-6">
        <h1 class="text-2xl font-bold mb-4">Edit Product #<?= $product['id'] ?></h1>
        <form action="admin_handler.php?action=edit_product" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">

            <div>
                <label class="block mb-1">Product Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block mb-1">Description</label>
                <textarea name="description" rows="4" class="w-full border p-2 rounded" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div>
                <label class="block mb-1">Price</label>
                <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block mb-1">Category</label>
                <select name="category" class="w-full border p-2 rounded" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $cat == $product['category'] ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block mb-1">Current Images</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($images as $img): ?>
                        <div class="relative group" data-image-wrapper="<?= htmlspecialchars($img) ?>">
                            <img src="uploads/<?= htmlspecialchars($img) ?>" class="w-24 h-24 object-cover rounded">
                            <button type="button"
                                    class="absolute top-0 right-0 bg-red-600 text-white w-6 h-6 rounded-full text-sm hidden group-hover:flex items-center justify-center delete-image-btn"
                                    data-product-id="<?= $product['id'] ?>"
                                    data-image="<?= htmlspecialchars($img) ?>">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>


            </div>

            <div>
                <label class="block mb-1">Add New Images</label>
                <input type="file" name="image[]" multiple class="w-full border p-2 rounded">
            </div>
            <div class="flex justify-between mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Changes</button>
            </div>
        </form>
    </div>

    <script src="admin.js"></script>
    <div id="toast" class="fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-white hidden opacity-0 z-50 transition-opacity duration-300"></div>

</body>
</html>
