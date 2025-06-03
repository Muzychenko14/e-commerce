<?php
session_start();
require_once('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST["login"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["admin"] = (bool)$user["is_admin"];

        if ($user["is_admin"]) {
            header("Location: admin/admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Wrong login or password!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | SneakUP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">Sign in to your account</h2>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
      <input 
        type="text" 
        name="login" 
        placeholder="Username or Email" 
        required 
        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
      >
      <input 
        type="password" 
        name="password" 
        placeholder="Password" 
        required 
        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
      >
      <button 
        type="submit" 
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition"
      >
        Login
      </button>
    </form>

    <p class="text-center text-sm text-gray-600 mt-4">
      Don't have an account?
      <a href="register.php" class="text-green-600 hover:text-yellow-300 font-medium transition-colors">Register now</a>
    </p>
  </div>

</body>
</html>

