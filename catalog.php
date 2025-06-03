<?php
session_start();
require_once('db.php');

// Проверка авторизации пользователя (если требуется)
// if (!isset($_SESSION["user_id"])) {
//     header("Location: login.php");
//     exit;
//}


$category_query = "SELECT DISTINCT category FROM products ORDER BY category ASC";
$category_result = $conn->query($category_query);
$categories = [];

if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Получение фильтра категории из GET-параметра
$category_filter = $_GET['category'] ?? '';

// Получение списка товаров с учетом фильтра
$sql = "SELECT * FROM products";
$params = [];

if (!empty($category_filter)) {
    $sql .= " WHERE category = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY id DESC LIMIT 12";
    $result = $conn->query($sql);
}

// Получение списка избранных товаров для текущего пользователя
$wishlist_ids = [];
if (isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];
    $wish_sql = "SELECT product_id FROM wishlist WHERE user_id = ?";
    $stmt = $conn->prepare($wish_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $wish_result = $stmt->get_result();
    while ($wish = $wish_result->fetch_assoc()) {
        $wishlist_ids[] = $wish["product_id"];
    }
} elseif (isset($_SESSION["wishlist"])) {
    $wishlist_ids = array_keys($_SESSION["wishlist"]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />

</head>
<body class="bg-gray-200 pt-[80px]"> 
    <button onclick="history.back()" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </button>
    <?php include 'header.php'; ?>

<div class="container mx-auto p-4">
    <div class="flex justify-between mb-4">
        <h2 class="text-2xl font-bold">Catalog</h2>
        <form method="GET" action="catalog.php">
            <select name="category" class="border rounded px-4 py-2" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($cat === $category_filter) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <section class="relative">
        <div class="overflow-x-auto whitespace-nowrap py-4 px-2 flex gap-4 scrollbar-hide" id="product-slider">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $images = json_decode($row['image'], true);
                    $imageSrc = !empty($images) ? 'admin/uploads/' . htmlspecialchars($images[0]) : 'default.jpg';
                    $productId = $row["id"];
                    $isInWishlist = in_array($productId, $wishlist_ids);
                    $heartClass = $isInWishlist ? 'fas text-red-500' : 'far text-gray-400';
                ?>
                <div class="snap-start w-72 flex-shrink-0 bg-white p-4 rounded-lg shadow-md">
                    <a href="product.php?id=<?= $productId ?>" class="block hover:shadow-xl transition duration-300">
                        <img src="<?= $imageSrc ?>" class="w-full h-64 object-cover rounded-t-lg" alt="<?= htmlspecialchars($row["name"]) ?>">
                        <div class="p-4">
                            <h2 class="text-lg font-bold my-2 text-blue-900 break-words line-clamp-2"><?= htmlspecialchars($row["name"]) ?></h2>
                            <p class="text-gray-600 text-sm mb-1">Category: <?= htmlspecialchars($row["category"]) ?></p>
                            <p class="text-green-600 font-semibold text-md mb-1">Price: $<?= number_format($row["price"], 2) ?></p>

                        </div>
                    </a>
                    <div class="flex justify-between items-center px-4 pb-4">
                        <button class="add-to-wishlist" data-id="<?= $productId ?>">
                            <i class="<?= $heartClass ?> fa-heart text-xl"></i>
                        </button>
                        <button class="add-to-cart bg-blue-500 text-white px-3 py-2 rounded text-sm" data-id="<?= $productId ?>">Add to cart</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
</div>

<?php include 'footer.php'; ?>
<script src="script.js"></script>
<div id="toast" class="fixed bottom-6 right-6 bg-black text-white px-4 py-2 rounded-lg shadow-md hidden z-50 transition-opacity duration-300"></div>
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>

</body>
</html>
