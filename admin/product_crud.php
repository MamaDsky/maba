<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

// Logika Hapus Produk (Delete)
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // Ambil file gambar produk dari server untuk dihapus fisiknya
    $img_res = $db->query("SELECT image_path FROM product_images WHERE product_id = $id");
    while ($img = $img_res->fetch_assoc()) {
        @unlink("../uploads/" . $img['image_path']);
    }
    // Ambil file sizechart jika ada
    $prod_res = $db->query("SELECT sizechart_path FROM products WHERE id = $id");
    $prod_data = $prod_res->fetch_assoc();
    if (!empty($prod_data['sizechart_path'])) {
        @unlink("../uploads/" . $prod_data['sizechart_path']);
    }

    $db->query("DELETE FROM products WHERE id = $id");
    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Produk berhasil dihapus dari katalog.'];
    header("Location: product_crud.php");
    exit;
}

// Logika Mengisi Form Kembali Saat Aksi Edit dipicu
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_res = $db->query("SELECT * FROM products WHERE id = $edit_id");
    $edit_data = $edit_res->fetch_assoc();
}

// Logika Tambah & Update Produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $name = htmlspecialchars($_POST['name']);
    $desc = htmlspecialchars($_POST['description']);
    $price = intval($_POST['price']);
    $type = $_POST['type'];
    $id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($id > 0) {
        // Mode UPDATE data inti produk
        $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, type = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $name, $desc, $price, $type, $id);
        $stmt->execute();
        $product_id = $id;
        $_SESSION['swal'] = ['type' => 'success', 'title' => 'Updated!', 'text' => 'Data produk berhasil diperbarui.'];
    } else {
        // Mode INSERT produk baru
        $stmt = $db->prepare("INSERT INTO products (name, description, price, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $desc, $price, $type);
        $stmt->execute();
        $product_id = $db->insert_id;
        $_SESSION['swal'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Produk baru masuk ke katalog.'];
    }

    // Pemrosesan File Upload Sizechart Gambar
    if (isset($_FILES['sizechart']) && $_FILES['sizechart']['error'] === 0) {
        $ext = pathinfo($_FILES['sizechart']['name'], PATHINFO_EXTENSION);
        $sc_filename = "SIZE_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['sizechart']['tmp_name'], "../uploads/" . $sc_filename)) {
            $db->query("UPDATE products SET sizechart_path = '$sc_filename' WHERE id = $product_id");
        }
    }

    // Pemrosesan File Upload 3 Gambar Tambahan (Hanya memproses jika admin memilih file baru)
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $files = $_FILES['images'];
        $total_uploaded = min(count($files['name']), 3);
        // Jika mode edit, bersihkan foto lama dulu agar tetap maks 3
        if ($id > 0) {
            $old_img = $db->query("SELECT image_path FROM product_images WHERE product_id = $id");
            while($oi = $old_img->fetch_assoc()) { @unlink("../uploads/" . $oi['image_path']); }
            $db->query("DELETE FROM product_images WHERE product_id = $id");
        }
        for ($i = 0; $i < $total_uploaded; $i++) {
            if ($files['error'][$i] === 0) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $new_filename = "IMG_" . uniqid() . "_" . $i . "." . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], "../uploads/" . $new_filename)) {
                    $db->query("INSERT INTO product_images (product_id, image_path) VALUES ($product_id, '$new_filename')");
                }
            }
        }
    }

    // Mengelola Relasi Bundle Ulang jika bertipe bundle
    if ($type == 'bundle' && isset($_POST['bundled_items'])) {
        $db->query("DELETE FROM bundle_relations WHERE bundle_product_id = $product_id");
        foreach ($_POST['bundled_items'] as $reg_id) {
            $reg_id = intval($reg_id);
            $db->query("INSERT INTO bundle_relations (bundle_product_id, regular_product_id) VALUES ($product_id, $reg_id)");
        }
    }

    header("Location: product_crud.php");
    exit;
}

// 🎛️ LOGIKA BACKEND PAGINATION PRODUK
$limit = 5; // Tampilkan 5 produk per halaman
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$total_res = $db->query("SELECT COUNT(*) as total FROM products");
$total_data = $total_res->fetch_assoc()['total'];
$pages = ceil($total_data / $limit);

$all_products = $db->query("SELECT p.*, (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as total_img FROM products p ORDER BY p.id DESC LIMIT $start, $limit");
$reg_products = $db->query("SELECT id, name FROM products WHERE type = 'reguler' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - CRUD Katalog & Bundle</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-8 text-sm">

    <!-- SweetAlert Session Handler -->
    <?php if(isset($_SESSION['swal'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['swal']['type']; ?>',
                title: '<?= $_SESSION['swal']['title']; ?>',
                text: '<?= $_SESSION['swal']['text']; ?>'
            });
        </script>
        <?php unset($_SESSION['swal']); ?>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto mb-4"><a href="index.php" class="text-indigo-600 hover:underline font-bold">← Kembali ke Dashboard Admin</a></div>
    
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Form Area (Tambah / Edit) -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 h-fit">
            <h3 class="text-base font-bold text-gray-900 mb-4"><?= $edit_data ? 'Form Edit Produk':'Tambah Produk Baru'; ?></h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php if($edit_data): ?>
                    <input type="hidden" name="product_id" value="<?= $edit_data['id']; ?>">
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nama Produk</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name'] ?? ''); ?>" required class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none"><?= htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Harga Jual (Rp)</label>
                    <input type="number" name="price" value="<?= $edit_data['price'] ?? ''; ?>" required class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tipe Produk</label>
                    <select name="type" id="product_type" onchange="toggleBundleBox()" class="w-full border border-gray-200 px-3 py-2 rounded-xl bg-white focus:outline-none">
                        <option value="reguler" <?= (isset($edit_data['type']) && $edit_data['type'] == 'reguler') ? 'selected':''; ?>>Reguler</option>
                        <option value="bundle" <?= (isset($edit_data['type']) && $edit_data['type'] == 'bundle') ? 'selected':''; ?>>Bundle (Paket Kombinasi)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Upload Sizechart (Gambar Panduan Ukuran)</label>
                    <input type="file" name="sizechart" accept="image/*" class="text-xs text-gray-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Gambar Produk (Maks 3, Biarkan kosong jika tak diubah)</label>
                    <input type="file" name="images[]" multiple accept="image/*" class="text-xs text-gray-500">
                </div>

                <!-- Open Bundle Checklist Area -->
                <div id="bundle_checklist_box" class="<?= (isset($edit_data['type']) && $edit_data['type'] == 'bundle') ? '':'hidden'; ?> bg-gray-50 p-4 rounded-xl border border-gray-200 max-h-40 overflow-y-auto">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Centang Isi Paket:</label>
                    <div class="space-y-1.5">
                        <?php while($reg = $reg_products->fetch_assoc()): ?>
                            <label class="flex items-center space-x-2 text-xs text-gray-700 cursor-pointer">
                                <input type="checkbox" name="bundled_items[]" value="<?= $reg['id']; ?>" class="rounded text-indigo-600">
                                <span><?= htmlspecialchars($reg['name']); ?></span>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <button type="submit" name="save_product" class="w-full bg-indigo-600 text-white font-semibold py-2 rounded-xl">Simpan Katalog</button>
                <?php if($edit_data): ?><a href="product_crud.php" class="block text-center text-xs text-gray-400 mt-2 hover:underline">Batal / Reset Mode Edit</a><?php endif; ?>
            </form>
        </div>

        <!-- Tabel Monitoring List Produk + Pagination -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 md:col-span-2 flex flex-col justify-between">
            <div class="overflow-x-auto">
                <h3 class="text-base font-bold text-gray-900 mb-4">Katalog Produk Terdaftar</h3>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-semibold uppercase">
                            <th class="pb-3">Nama Produk</th>
                            <th class="pb-3">Tipe</th>
                            <th class="pb-3">Harga</th>
                            <th class="pb-3">Media (Foto / SC)</th>
                            <th class="pb-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-700">
                        <?php while($p = $all_products->fetch_assoc()): ?>
                        <tr>
                            <td class="py-3 font-medium text-gray-900"><?= htmlspecialchars($p['name']); ?></td>
                            <td class="py-3"><span class="px-2 py-0.5 rounded text-xs <?= $p['type'] == 'bundle'?'bg-purple-50 text-purple-600 font-bold':'bg-gray-100 text-gray-600'; ?>"><?= $p['type']; ?></span></td>
                            <td class="py-3 font-semibold">Rp<?= number_format($p['price'],0,',','.'); ?></td>
                            <td class="py-3 text-xs text-gray-400">🖼️ <?= $p['total_img']; ?> foto | <?= !empty($p['sizechart_path']) ? '✅ Ada SC' : '❌ No SC'; ?></td>
                            <td class="py-3 text-right space-x-2">
                                <a href="product_crud.php?edit_id=<?= $p['id']; ?>" class="text-indigo-600 hover:underline">Edit</a>
                                <button onclick="confirmDelete(<?= $p['id']; ?>)" class="text-red-500 hover:underline">Hapus</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Navigasi Elemen Pagination -->
            <div class="flex justify-between items-center border-t border-gray-100 pt-4 mt-6">
                <span class="text-xs text-gray-400">Total data: **<?= $total_data; ?>** item</span>
                <div class="flex space-x-1">
                    <?php for($i=1; $i<=$pages; $i++): ?>
                        <a href="product_crud.php?page=<?= $i; ?>" class="px-3 py-1 text-xs rounded-lg font-medium <?= $page == $i ? 'bg-indigo-600 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>"><?= $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleBundleBox() {
        let type = document.getElementById('product_type').value;
        document.getElementById('bundle_checklist_box').classList.toggle('hidden', type !== 'bundle');
    }
    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah kamu yakin?',
            text: "Data produk dan berkas file gambar fisik akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'product_crud.php?delete_id=' + id;
            }
        });
    }
    </script>
</body>
</html>