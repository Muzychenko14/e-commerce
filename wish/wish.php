<?php require_once('../db.php');?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 pt-[80px]">


    <?php include '../header.php'; ?>

    <div class="container mx-auto p-2">
        <h1 class="text-2xl font-bold mb-4">❤️ Wishlist </h1>
        <div class="p-2">
            <button onclick="history.back()" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </button>
        </div>
        <div id="wishlist" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">

        </div>
    </div>


    <?php include '../footer.php'; ?>

    <script src="wish.js?v=123"></script>

    <div id="toast" class="fixed bottom-6 right-6 hidden opacity-0 px-5 py-3 rounded-lg shadow-lg text-white z-50 transition-opacity duration-300"></div>

</body>
</html>
