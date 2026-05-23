d<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Ambil semua produk, sertakan link gambar pertamanya saja sebagai thumbnail utama
$query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image FROM products p ORDER BY p.id DESC";
$all_products = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Katalog Produk MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans text-sm">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm py-4 px-8 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="text-xl font-bold tracking-tight text-indigo-600">Maba<span class="text-gray-900">Store.</span></a>
        <div class="space-x-6 font-medium text-sm">
            <a href="index.php" class="text-gray-600 hover:text-indigo-600">Home</a>
            <a href="products.php" class="text-indigo-600">Semua Produk</a>
            <a href="track.php" class="text-gray-600 hover:text-indigo-600">Lacak Pesanan</a>
        </div>
    </nav>

    <!-- Konten Utama Katalog -->
    <main class="max-w-6xl mx-auto px-6 py-12">
        <div class="mb-10 text-center md:text-left">
            <h1 class="text-3xl font-extrabold text-gray-950 tracking-tight">Semua Koleksi Perlengkapan</h1>
            <p class="text-gray-400 text-xs mt-1">Cari dan temukan kebutuhan ospek serta perlengkapan asrama maba secara lengkap.</p>
        </div>

        <!-- Grid Produk -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php if($all_products->num_rows > 0): ?>
                <?php while($row = $all_products->fetch_assoc()): ?>
                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition flex flex-col justify-between">
                    <!-- Thumbnail Produk -->
                    <img src="uploads/<?= $row['main_image'] ?? 'placeholder.jpg'; ?>" class="w-full h-48 object-cover bg-gray-100">
                    
                    <!-- Detail Informasi -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-xxs font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 uppercase tracking-wider"><?= $row['type']; ?></span>
                            <h3 class="font-bold text-gray-900 mt-2 text-base leading-snug"><?= htmlspecialchars($row['name']); ?></h3>
                            <p class="text-gray-400 text-xs line-clamp-2 mt-1"><?= htmlspecialchars($row['description']); ?></p>
                        </div>
                        <div class="mt-5">
                            <p class="text-indigo-600 font-black text-base">Rp <?= number_format($row['price'], 0, ',', '.'); ?></p>
                            <div class="grid grid-cols-2 gap-2 mt-3">
                                <a href="detail.php?id=<?= $row['id']; ?>" class="text-center text-xs border border-gray-200 text-gray-600 py-2 rounded-xl hover:bg-gray-50 font-semibold transition">Detail</a>
                                <button onclick="addToCart(<?= $row['id']; ?>)" class="bg-indigo-600 text-white text-xs py-2 rounded-xl font-semibold hover:bg-indigo-700 transition">+ Keranjang</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12 text-gray-400 italic">
                    Belum ada produk yang di-upload oleh admin.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Cart UI -->
    <div class="fixed bottom-6 right-6 z-50">
        <a href="checkout.php" class="bg-gray-900 hover:bg-gray-800 text-white px-5 py-4 rounded-full shadow-xl flex items-center space-x-3 text-sm font-semibold transition">
            <span>🛒</span>
            <span>Keranjang Belanja</span>
            <span id="cart-counter" class="bg-indigo-600 px-2 py-0.5 rounded-full text-xs"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
        </a>
    </div>

    <!-- Script AJAX untuk Cart Controller -->
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
                Swal.fire({
  title: "Sukses",
  text: "Produk Anda berhasil ditambahkan di keranjang",
  icon: "success"
});
            }
        });
    }
    </script>
</body>
</html>