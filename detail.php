<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) { die("Produk tidak ditemukan."); }

// Ambil Gambar Detail (Maksimal 3)
$images = $db->query("SELECT image_path FROM product_images WHERE product_id = $id LIMIT 3");

// Ambil Isi Komponen Bundle jika produk bertipe bundle
$bundle_items = [];
if ($product['type'] == 'bundle') {
    $bi_res = $db->query("SELECT p.name FROM bundle_relations br JOIN products p ON br.regular_product_id = p.id WHERE br.bundle_product_id = $id");
    while($row = $bi_res->fetch_assoc()) { 
        $bundle_items[] = $row['name']; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']); ?> - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 text-sm text-gray-800">

    <nav class="bg-white shadow-sm py-4 px-8 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="text-xl font-bold tracking-tight text-indigo-600">Maba<span class="text-gray-900">Store.</span></a>
        <div class="space-x-6 font-medium text-sm">
            <a href="index.php" class="text-gray-600 hover:text-indigo-600">Home</a>
            <a href="products.php" class="text-gray-600 hover:text-indigo-600">Semua Produk</a>
            <a href="track.php" class="text-gray-600 hover:text-indigo-600">Lacak Pesanan</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto py-12 px-6">
        <div class="bg-white rounded-3xl shadow-xs border border-gray-100 overflow-hidden grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="p-6 bg-gray-50 flex flex-col justify-center">
                <div class="flex space-x-3 overflow-x-auto snap-x snap-mandatory pb-4 scrollbar-none">
                    <?php if($images->num_rows > 0): ?>
                        <?php while($img = $images->fetch_assoc()): ?>
                            <img src="uploads/<?= $img['image_path']; ?>" class="w-full h-96 object-cover rounded-2xl snap-center shrink-0 shadow-xs border bg-white">
                        <?php endwhile; ?>
                    <?php else: ?>
                        <img src="uploads/placeholder.jpg" class="w-full h-96 object-cover rounded-2xl snap-center shrink-0 border bg-white">
                    <?php endif; ?>
                </div>
                <p class="text-center text-xxs text-gray-400 mt-2 italic">← Geser gambar ke samping untuk melihat detail lainnya →</p>
            </div>

            <div class="p-8 md:p-10 flex flex-col justify-between">
                <div class="space-y-5">
                    <div>
                        <span class="bg-indigo-50 text-indigo-600 text-xxs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider"><?= $product['type']; ?></span>
                        <h1 class="text-2xl md:text-3xl font-black text-gray-950 mt-3 leading-tight"><?= htmlspecialchars($product['name']); ?></h1>
                        <p class="text-2xl font-extrabold text-indigo-600 mt-2">Rp <?= number_format($product['price'], 0, ',', '.'); ?></p>
                    </div>
                    
                    <div class="border-t border-gray-100 pt-4 space-y-4">
                        <div class="text-gray-500 leading-relaxed">
                            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Deskripsi Produk:</span>
                            <?= nl2br(htmlspecialchars($product['description'])); ?>
                        </div>

                        <?php if(!empty($bundle_items)): ?>
                        <div class="bg-indigo-50/70 p-4 rounded-2xl border border-indigo-100/50">
                            <strong class="text-indigo-950 text-xs tracking-wide block mb-2">📦 Isi Paket Kombinasi (Bundle):</strong>
                            <ul class="space-y-1.5">
                                <?php foreach($bundle_items as $item): ?>
                                    <li class="text-indigo-700 text-xs flex items-center font-medium">
                                        <span class="mr-2 text-indigo-500">✔</span> <?= htmlspecialchars($item); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($product['sizechart_path'])): ?>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 flex justify-between items-center">
                            <div>
                                <strong class="text-gray-900 text-xs block">📏 Panduan Ukuran (Sizechart)</strong>
                                <span class="text-xxs text-gray-400 block mt-0.5">Pastikan ukuran pas sebelum checkout</span>
                            </div>
                            <button onclick="showSizechart('uploads/<?= $product['sizechart_path']; ?>')" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs px-4 py-2 rounded-xl font-bold transition shadow-2xs">Lihat Gambar</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 grid grid-cols-2 gap-4">
                    <a href="products.php" class="text-center py-3.5 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition">Kembali</a>
                    <button onclick="addToCart(<?= $product['id']; ?>)" class="py-3.5 bg-indigo-600 text-white rounded-xl font-bold shadow-md hover:bg-indigo-700 transition"> + Masuk Keranjang</button>
                </div>
            </div>

        </div>
    </div>

    <script>
    function showSizechart(url) {
        Swal.fire({
            title: 'Panduan Ukuran Produk',
            imageUrl: url,
            imageAlt: 'Sizechart Pelanggan',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl' }
        });
    }

    function addToCart(id) {
        let formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', id);

        fetch('cart_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Masuk Keranjang!',
                    text: 'Silakan lanjutkan belanja atau langsung checkout.',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'Lanjut Checkout 🛒',
                    cancelButtonText: 'Belanja Lagi'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'checkout.php';
                    } else {
                        window.location.href = 'products.php';
                    }
                });
            }
        });
    }
    </script>
</body>
</html>