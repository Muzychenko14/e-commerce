<?php
session_start();
require_once('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $repassword = $_POST["repassword"];

    if ($password !== $repassword) {
        $error = "Passwords do not match!";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $passwordHash);

        if ($stmt->execute()) {
            $_SESSION["user_id"] = $stmt->insert_id;
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | SneakUP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">Create a new account</h2>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="space-y-4">
      <input 
        type="text" 
        name="username" 
        placeholder="Username" 
        required 
        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
      >
      <input 
        type="email" 
        name="email" 
        placeholder="Email" 
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
      <input 
        type="password" 
        name="repassword" 
        placeholder="Repeat Password" 
        required 
        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
      >
      <button 
        type="submit" 
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition"
      >
        Register
      </button>
    </form>

    <p class="text-center text-sm text-gray-600 mt-4">
      Already have an account?
      <a href="login.php" class="text-green-600 hover:text-yellow-300 font-medium transition-colors">Login</a>
    </p>
  </div>

</body>
</html>
