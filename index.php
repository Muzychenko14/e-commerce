<?php
session_start();
require_once('db.php');

if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE products ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'Unisex'");
}

if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

if (isset($_GET['view_product'])) {
    $product_id = intval($_GET['view_product']);
    if (!in_array($product_id, $_SESSION['recently_viewed'])) {
        $_SESSION['recently_viewed'][] = $product_id;
        if (count($_SESSION['recently_viewed']) > 3) {
            array_shift($_SESSION['recently_viewed']);
        }
    }
    header("Location: index.php");
    exit();
}

if (isset($_GET['clear_recent'])) {
    $_SESSION['recently_viewed'] = [];
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SneakUP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!--<link rel="stylesheet" href="styles.css"> -->
</head>

<?php include 'header.php'; ?>

<body class="bg-gray-100">    
    
    <section class="relative w-full h-[120vh] overflow-hidden bg-black">
        
        <div class="absolute inset-0 bg-[url('uploads/screen_upper.jpg')] bg-cover bg-no-repeat bg-center z-0"></div>
        <div class="absolute inset-0 bg-black/60 z-10"></div>
        <div class="relative z-20 flex flex-col items-center justify-center text-center h-full px-4 text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Find your perfect sneakers</h1>
            <p class="text-lg md:text-xl mb-6 max-w-xl">5 years on the market. Only original. Style that speaks for you.</p>
            <a href="catalog.php" class="bg-white text-black font-semibold px-6 py-3 rounded hover:bg-gray-200 transition">View catalog</a>
        </div>
    </section>

    <div class="container mx-auto p-4">
        <!-- <div class="flex justify-between mb-4">
            <h2 class="text-2xl font-bold">Sneakers</h2>
            <select id="category-filter" class="border rounded px-4 py-2" onchange="filterByCategory(this)">
                <option value="">All Categories</option>
                <?php
                $category_query = "SELECT name FROM categories ORDER BY name ASC";
                $category_result = $conn->query($category_query);

                if (!$category_result) {
                    die("Ошибка SQL при получении категорий: " . $conn->error);
                }

                while ($category_row = $category_result->fetch_assoc()) {
                    $cat = htmlspecialchars($category_row['name']);
                    $selected = ($cat === $category_filter) ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>
        </div>-->

        <?php

        $sql = "SELECT * FROM products ORDER BY id DESC LIMIT 12";
        $result = $conn->query($sql);

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
                            <img src="<?= $imageSrc ?>" class="w-full h-64 object-cover rounded-t-lg" alt="Sneaker">
                            <div class="p-4">
                                <h2 class="text-lg font-bold my-2 text-blue-900 break-words line-clamp-2"><?= htmlspecialchars($row["name"]) ?></h2>
                            </div>
                        </a>
                        <div class="flex justify-between items-center px-4 pb-4">
                            <button class="add-to-wishlist" data-id="<?= $productId ?>">
                                <i class="<?= $heartClass ?> fa-heart text-xl"></i>
                            </button>
                            <button class="add-to-cart bg-blue-500 text-white px-3 py-2 rounded text-sm" data-id="<?= $productId ?>">Add to Cart</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <section class="relative w-full h-[90vh] overflow-hidden bg-black">
        <div class="absolute inset-0 bg-[url('uploads/screen_lower.jpg')] bg-cover bg-no-repeat bg-center z-0"></div>
        <div class="absolute inset-0 bg-black/60 z-10"></div>
        <div class="relative z-20 flex flex-col items-center justify-center text-center h-full px-4 text-white">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Discover the world of style</h1>
            <p class="text-lg md:text-xl mb-6 max-w-xl">With us, your feet will look so sweet.</p>
            <a href="catalog.php" class="bg-white text-black font-semibold px-6 py-3 rounded hover:bg-gray-200 transition">View catalog</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script src="script.js"></script>

    <div id="toast" class="fixed bottom-6 right-6 bg-black text-white px-4 py-2 rounded-lg shadow-md hidden z-50 transition-opacity duration-300"></div>

</body>
</html>
