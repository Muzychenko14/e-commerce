<?php 
session_start();
require_once('../db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-200 pt-[80px]">
    <?php include '../header.php'; ?>

    <div class="container mx-auto p-2">
        <h1 class="text-2xl font-bold mb-4">🛒 Cart</h1>
        <div class="p-2">
            <button onclick="history.back()" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </button>
            </div>
        <div id="cart" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Товары в корзине -->
        </div>

        <button id="checkout-button" class="bg-green-500 text-white px-4 py-2 rounded mt-4 hidden">Checkout</button>



    </div>


    
    <?php include '../footer.php'; ?>

    <script src="cart.js?v=123" defer></script>
</body>
</html>
