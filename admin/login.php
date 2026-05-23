<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen px-6">
    <div class="max-w-sm w-full bg-white border border-gray-100 p-8 rounded-3xl shadow-xs">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Admin Panel</h2>
            <p class="text-gray-400 text-xs mt-1">Silakan masuk untuk mengelola Pre-Order maba</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 text-xs p-3 rounded-xl text-center mb-4 font-medium">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" required class="w-full border border-gray-200 px-4 py-2.5 rounded-xl focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-200 px-4 py-2.5 rounded-xl focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                Masuk Dashboard
            </button>
        </form>
    </div>
</body>
</html>