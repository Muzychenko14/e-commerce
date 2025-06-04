<?php
require_once('db.php');

$path_prefix = '';
if (strpos($_SERVER['PHP_SELF'], '/cart/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/wish/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/profile/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $path_prefix = '../';
}
?>

<header id="main-header" class="fixed top-0 left-0 w-full z-50 transition-transform duration-300 transform bg-[var(--red)] bg-opacity-80 shadow-md p-4 flex justify-between items-center text-black backdrop-blur-md">
  
    <div class='flex items-center'>
    <a href="<?= $path_prefix ?>index.php" class="text-2xl font-bold text-zinc-400 hover:text-yellow-100 transition-colors">SneakUp</a>
    </div>

    <div class="relative w-full max-w-md mx-auto">
        <input type="text" id="search-input"
            placeholder="Search..."
            class="w-full p-2 border border-zinc-200 rounded text-zinc-400 focus:outline-none focus:ring-2 focus:ring-yellow-100 placeholder-zinc-400" />

        <div id="search-results"
            class="absolute top-full left-0 right-0 bg-white border border-zinc-200 rounded shadow-md mt-1 hidden z-50 max-h-72 overflow-y-auto">
        </div>
    </div>



    <div class="flex items-center space-x-4">
        <a href="<?= $path_prefix ?>wish/wish.php" class='text-2xl text-zinc-400 hover:text-yellow-100'><i class="fas fa-heart"></i></a>
        <a href="<?= $path_prefix ?>cart/cart.php" class='text-2xl text-zinc-400 hover:text-yellow-100'><i class="fas fa-shopping-cart"></i></a>
        
        <?php if (isset($_SESSION["user_id"])): ?>
            
            <a href="<?= $path_prefix ?>profile/profile.php" class='text-2xl text-zinc-400 hover:text-yellow-100'><i class="fas fa-user"></i></a>
            <a href="<?= $path_prefix ?>logout.php" class='text-2xl text-zinc-400 hover:text-yellow-100'><i class="fas fa-sign-out-alt"></i></a>

            <?php if (isset($_SESSION["admin"]) && $_SESSION["admin"] === true): ?>
                
                <a href="<?= $path_prefix ?>admin/admin.php" class='text-2xl text-lime-400 hover:text-yellow-200'><i class="fas fa-cogs"></i></a>
            <?php endif; ?>

        <?php else: ?>
            
            <a href="<?= $path_prefix ?>login.php" class='text-2xl text-zinc-400 hover:text-yellow-100'><i class="fa-solid fa-right-to-bracket"></i></a>
            <a href="<?= $path_prefix ?>register.php" class='text-2xl text-zinc-400 hover:text-yellow-100'><i class="fas fa-user-plus"></i></a>
        <?php endif; ?>
    </div>
</header>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    if (searchInput && searchResults) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.trim();

            if (query.length > 2) {
                fetch('<?= $path_prefix ?>search.php?q=' + encodeURIComponent(query))
                    .then(res => res.text())
                    .then(data => {
                        searchResults.innerHTML = data;
                        searchResults.classList.remove('hidden');
                    });
            } else {
                searchResults.classList.add('hidden');
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }
});
</script>

