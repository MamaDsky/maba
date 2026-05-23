<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Cek apakah username sudah terdaftar
        $check_stmt = $db->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'Username sudah digunakan, silakan pilih username lain!';
        } else {
            // Enkripsi password dengan Bcrypt yang aman
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Masukkan admin baru ke database
            $stmt = $db->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                $message = 'Akun Admin berhasil dibuat! Silakan login.';
            } else {
                $error = 'Gagal mendaftarkan akun, terjadi kesalahan sistem.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Admin Sementara - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen px-6">
    <div class="max-w-sm w-full bg-white border border-gray-100 p-8 rounded-3xl shadow-xs">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Register Admin</h2>
            <p class="text-gray-400 text-xs mt-1">Buat akun admin baru untuk mengelola platform</p>
        </div>

        <!-- Notifikasi Eror -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 text-xs p-3 rounded-xl text-center mb-4 font-medium">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <!-- Notifikasi Sukses -->
        <?php if (!empty($message)): ?>
            <div class="bg-green-50 border border-green-100 text-green-600 text-xs p-3 rounded-xl text-center mb-4 font-medium">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Username Baru</label>
                <input type="text" name="username" required class="w-full border border-gray-200 px-4 py-2.5 rounded-xl focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-200 px-4 py-2.5 rounded-xl focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Konfirmasi Password</label>
                <input type="password" name="confirm_password" required class="w-full border border-gray-200 px-4 py-2.5 rounded-xl focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                Daftar Akun Admin
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="login.php" class="text-xs text-indigo-600 hover:underline font-medium">← Kembali ke Halaman Login</a>
        </div>
    </div>
</body>
</html>