<?php
session_start();
require_once('../db.php');


if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['delete_user_id'])) {
    $id_to_delete = intval($_POST['delete_user_id']);

    if ($_SESSION['user_id'] != $id_to_delete) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        $stmt->execute();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = intval($_POST['delete_user_id']);
    if ($delete_id !== $_SESSION['user_id']) { 
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
    }
}


$result = $conn->query("
    SELECT 
        u.id, u.username, u.email, u.avatar, u.is_admin, u.created_at,
        GROUP_CONCAT(
            CONCAT_WS(', ', a.street, a.city, a.postal_code, a.country)
            SEPARATOR ' | '
        ) AS full_address,
        p.payment_method
    FROM users u
    LEFT JOIN addresses a ON u.id = a.user_id
    LEFT JOIN payments p ON u.id = p.user_id
    GROUP BY u.id
    ORDER BY u.id
");




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php include '../header.php'; ?>
<body class="bg-gray-100 p-6 pt-[80px]">
    <div class="p-4">
        <a href="admin.php" class="inline-flex items-center text-gray-700 hover:text-black text-sm font-medium transition duration-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>
    <h1 class="text-3xl font-bold mb-6 text-center">User Management</h1>
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-800">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Username</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Address</th>
                        <th class="px-4 py-2 text-left">Payment</th>
                        <th class="px-4 py-2 text-center">Avatar</th>
                        <th class="px-4 py-2 text-center">Admin?</th>
                        <th class="px-4 py-2 text-left">Created</th>
                        <th class="px-4 py-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($user = $result->fetch_assoc()): ?>
                    <tr class="even:bg-gray-50">
                        <td class="px-4 py-2"><?= $user['id'] ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="px-4 py-2">
                            <?= $user['full_address'] ? nl2br(htmlspecialchars(str_replace(' | ', "\n", $user['full_address']))) : '<span class="text-gray-400 italic">No address</span>' ?>
                        </td>
                        <td class="px-4 py-2"><?= $user['payment_method'] ? htmlspecialchars($user['payment_method']) : '<span class="text-gray-400 italic">None</span>' ?></td>
                        <td class="px-4 py-2 text-center">
                            <?php if ($user['avatar']): ?>
                                <img src="/progetto/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="avatar" class="w-8 h-8 rounded-full mx-auto">
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <?= $user['is_admin'] ? '<span class="text-green-600 font-semibold">Yes</span>' : 'No' ?>
                        </td>
                        <td class="px-4 py-2"><?= htmlspecialchars($user['created_at']) ?></td>
                        <td class="px-4 py-2 text-center">
                            <?php if ($_SESSION['user_id'] != $user['id']): ?>
                            <form method="POST" onsubmit="return confirm('Delete user <?= htmlspecialchars($user['username']) ?>?')">
                                <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                            </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <script src="admin.js"></script>
    <?php include '../footer.php'; ?>
</body>
</html>