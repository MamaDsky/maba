<?php
require_once '../config/database.php';

// Proteksi halaman admin: kalau belum login, tendang ke login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = (new Database())->getConnection();

// 1. Hitung Total Pendapatan
$income_q = $db->query("SELECT SUM(total_price) as total FROM orders");
$income = $income_q->fetch_assoc()['total'] ?? 0;

// 2. Hitung Total Pesanan Masuk
$orders_q = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $orders_q->fetch_assoc()['total'] ?? 0;

// 3. Hitung Total Produk di Katalog
$products_q = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $products_q->fetch_assoc()['total'] ?? 0;

// 4. Ambil Info Batch PO yang Sedang Aktif
$batch_q = $db->query("SELECT batch_name FROM batches WHERE is_active = 1 LIMIT 1");
$active_batch = $batch_q->fetch_assoc()['batch_name'] ?? 'Tidak Ada Batch Aktif';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans text-sm">

    <div class="flex h-screen">
        <!-- Sidebar Menu Navigasi Admin -->
        <aside class="w-64 bg-white border-r border-gray-100 p-6 flex flex-col justify-between">
            <div class="space-y-8">
                <div class="text-lg font-bold tracking-tight text-indigo-600 px-2">MabaStore <span class="text-gray-400 text-xs font-normal block">Admin Console</span></div>
                <nav class="space-y-1">
                    <a href="index.php" class="bg-indigo-50 text-indigo-600 font-semibold block px-4 py-2.5 rounded-xl transition">📊 Dashboard</a>
                    <a href="product_crud.php" class="text-gray-600 hover:bg-gray-50 block px-4 py-2.5 rounded-xl transition">📦 CRUD Katalog & Bundle</a>
                    <a href="orders.php" class="text-gray-600 hover:bg-gray-50 block px-4 py-2.5 rounded-xl transition">📋 Data Pesanan</a>
                    <a href="batch.php" class="text-gray-600 hover:bg-gray-50 block px-4 py-2.5 rounded-xl transition">🚀 Pengaturan Batch PO</a>
                </nav>
            </div>
            <div>
                <a href="logout.php" class="text-red-500 hover:bg-red-50 font-medium block px-4 py-2.5 rounded-xl transition text-center border border-red-100">🚪 Log Out</a>
            </div>
        </aside>

        <!-- Konten Utama Dashboard -->
        <main class="flex-1 p-10 overflow-y-auto">
            <header class="flex justify-between items-center mb-10">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-950">Selamat Datang, <?= htmlspecialchars($_SESSION['admin_username']); ?>! 👋</h1>
                    <p class="text-gray-400 text-xs mt-0.5">Berikut statistik performa penjualan Pre-Order keperluan maba saat ini.</p>
                </div>
                <div class="bg-indigo-600 text-white px-4 py-2 rounded-xl font-medium text-xs shadow-xs">
                    🟢 Active Batch: <span class="font-bold underline"><?= $active_batch; ?></span>
                </div>
            </header>

            <!-- Grid Kartu Statistik Bento-Style -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white border border-gray-100 p-6 rounded-2xl shadow-xs">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Omzet Pendapatan</p>
                    <p class="text-3xl font-black text-indigo-600">Rp <?= number_format($income, 0, ',', '.'); ?></p>
                    <p class="text-gray-400 text-xxs mt-2">*Akumulasi dari seluruh transaksi masuk</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-white border border-gray-100 p-6 rounded-2xl shadow-xs">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Jumlah Pesanan Masuk</p>
                    <p class="text-3xl font-black text-gray-900"><?= $total_orders; ?> <span class="text-sm font-medium text-gray-400">Pesanan</span></p>
                    <p class="text-indigo-600 text-xxs mt-2 font-medium"><a href="orders.php" class="underline">Lihat & Proses Pesanan →</a></p>
                </div>
                <!-- Card 3 -->
                <div class="bg-white border border-gray-100 p-6 rounded-2xl shadow-xs">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Item di Katalog</p>
                    <p class="text-3xl font-black text-gray-900"><?= $total_products; ?> <span class="text-sm font-medium text-gray-400">Produk</span></p>
                    <p class="text-indigo-600 text-xxs mt-2 font-medium"><a href="product_crud.php" class="underline">Tambah Produk / Bundle →</a></p>
                </div>
            </div>
        </main>
    </div>

</body>
</html>