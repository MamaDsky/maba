<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Ambil Batch Aktif
$batch_query = "SELECT * FROM batches WHERE is_active = 1 LIMIT 1";
$batch_res = $db->query($batch_query);
$active_batch = $batch_res->fetch_assoc();

// Ambil Produk Populer / Terbaru untuk Etalase
$prod_query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image FROM products p ORDER BY p.id DESC LIMIT 4";
$products = $db->query($prod_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MabaStore - Keperluan Maba Lengkap</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm py-4 px-8 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="text-xl font-bold tracking-tight text-indigo-600">Maba<span class="text-gray-900">Store.</span></a>
        <div class="space-x-6 font-medium text-sm">
            <a href="index.php" class="text-indigo-600">Home</a>
            <a href="products.php" class="text-gray-600 hover:text-indigo-600">Semua Produk</a>
            <a href="track.php" class="text-gray-600 hover:text-indigo-600">Lacak Pesanan</a>
        </div>
    </nav>

    <!-- Banner Pre-Order -->
    <div class="bg-indigo-600 text-white text-center py-3 text-sm font-semibold tracking-wide">
        🚀 Lewat Antrean! Sekarang Sedang Dibuka: <span class="underline"><?= $active_batch ? $active_batch['batch_name'] : 'Tidak Ada Batch Aktif'; ?></span>
    </div>

    <!-- Hero Section -->
    <header class="max-w-6xl mx-auto px-6 py-16 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight mb-4">Persiapan Kuliah Gak Pake Ribet.</h1>
        <p class="text-gray-500 max-w-xl mx-auto mb-8 text-base">Semua paket perlengkapan ospek, asrama, dan kemeja formal maba tersedia di satu tempat dengan sistem Pre-Order aman.</p>
        <a href="products.php" class="bg-gray-900 hover:bg-gray-800 text-white font-medium px-6 py-3 rounded-xl shadow-sm transition">Lihat Katalog Produk</a>
    </header>

    <!-- Etalase Produk Dinamis -->
    <section class="max-w-6xl mx-auto px-6 py-12">
        <h2 class="text-2xl font-bold mb-8 text-gray-900">Rekomendasi Produk Terbaru</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php while($row = $products->fetch_assoc()): ?>
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition flex flex-col justify-between">
                <img src="uploads/<?= $row['main_image'] ?? 'placeholder.jpg'; ?>" class="w-full h-48 object-cover">
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold px-2 py-1 rounded bg-indigo-50 text-indigo-600 uppercase tracking-wider"><?= $row['type']; ?></span>
                        <h3 class="font-bold text-gray-900 mt-2 text-lg leading-snug"><?= htmlspecialchars($row['name']); ?></h3>
                    </div>
                    <div class="mt-4">
                        <p class="text-indigo-600 font-extrabold text-lg">Rp <?= number_format($row['price'], 0, ',', '.'); ?></p>
                        <div class="grid grid-cols-2 gap-2 mt-3">
                            <a href="detail.php?id=<?= $row['id']; ?>" class="text-center text-xs border border-gray-200 text-gray-600 py-2 rounded-xl hover:bg-gray-50 font-medium">Detail</a>
                            <button onclick="addToCart(<?= $row['id']; ?>)" class="bg-indigo-600 text-white text-xs py-2 rounded-xl font-medium hover:bg-indigo-700 transition"> + Keranjang</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Floating Cart UI -->
    <div class="fixed bottom-6 right-6 z-50">
        <a href="checkout.php" class="bg-gray-900 hover:bg-gray-800 text-white px-5 py-4 rounded-full shadow-xl flex items-center space-x-3 text-sm font-semibold transition">
            <span>🛒</span>
            <span>Keranjang Belanja</span>
            <span id="cart-counter" class="bg-indigo-600 px-2 py-0.5 rounded-full text-xs"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
        </a>
    </div>

    <script>
    function addToCart(id) {
        let formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', id);

        fetch('cart_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('cart-counter').innerText = data.total_items;
                alert('Produk berhasil dimasukkan ke keranjang!');
            }
        });
    }
    </script>
</body>
</html>