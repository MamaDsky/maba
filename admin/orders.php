<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

// Proses Aksi Hapus Pesanan (Delete)
if (isset($_GET['delete_id'])) {
    $order_id = intval($_GET['delete_id']);
    $db->query("DELETE FROM orders WHERE id = $order_id");
    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Terhapus!', 'text' => 'Data pesanan berhasil dibersihkan dari log database.'];
    header("Location: orders.php");
    exit;
}

// Logika Edit Informasi Identitas Pesanan & Resi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_order_edit'])) {
    $order_id = intval($_POST['order_id']);
    $name = htmlspecialchars($_POST['customer_name']);
    $phone = htmlspecialchars($_POST['customer_phone']);
    $address = htmlspecialchars($_POST['customer_address']);
    $status = $_POST['status'];
    $receipt = htmlspecialchars($_POST['receipt_number']);

    $stmt = $db->prepare("UPDATE orders SET customer_name = ?, customer_phone = ?, customer_address = ?, status = ?, receipt_number = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $phone, $address, $status, $receipt, $order_id);
    $stmt->execute();

    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Berhasil Diubah!', 'text' => 'Data transaksi dan log resi berhasil diperbarui.'];
    header("Location: orders.php");
    exit;
}

// Logika Pengisian data Form Saat Tombol Edit Dipicu
$edit_order = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_order = $db->query("SELECT * FROM orders WHERE id = $edit_id")->fetch_assoc();
}

// 🎛️ LOGIKA BACKEND PAGINATION ORDERS
$limit = 10; // 10 orderan per halaman
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$filter_status = $_GET['status'] ?? '';
$filter_batch = $_GET['batch_id'] ?? '';

$where_clause = " WHERE 1=1";
if (!empty($filter_status)) { $where_clause .= " AND o.status = '$filter_status'"; }
if (!empty($filter_batch)) { $where_clause .= " AND o.batch_id = " . intval($filter_batch); }

$total_res = $db->query("SELECT COUNT(*) as total FROM orders o $where_clause");
$total_data = $total_res->fetch_assoc()['total'];
$pages = ceil($total_data / $limit);

$orders = $db->query("SELECT o.*, b.batch_name FROM orders o JOIN batches b ON o.batch_id = b.id $where_clause ORDER BY o.id DESC LIMIT $start, $limit");
$all_batches = $db->query("SELECT id, batch_name FROM batches ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manajemen Pesanan</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-8 text-sm">

    <?php if(isset($_SESSION['swal'])): ?>
        <script>Swal.fire({ icon: '<?= $_SESSION['swal']['type']; ?>', title: '<?= $_SESSION['swal']['title']; ?>', text: '<?= $_SESSION['swal']['text']; ?>' });</script>
        <?php unset($_SESSION['swal']); ?>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto mb-4"><a href="index.php" class="text-indigo-600 hover:underline font-bold">← Kembali ke Dashboard Admin</a></div>

    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Tabel Data Utama Masuk -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 lg:col-span-2 flex flex-col justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Daftar Pre-Order Maba</h2>

                <!-- Form Filter Area -->
                <form method="GET" class="flex gap-2 mb-6 p-3 bg-gray-50 rounded-xl">
                    <select name="status" class="border border-gray-200 bg-white px-2 py-1.5 rounded-lg text-xs">
                        <option value="">Semua Status</option>
                        <option value="Diproses" <?= $filter_status=='Diproses'?'selected':''; ?>>Diproses</option>
                        <option value="Di-packing" <?= $filter_status=='Di-packing'?'selected':''; ?>>Di-packing</option>
                        <option value="Dikirim" <?= $filter_status=='Dikirim'?'selected':''; ?>>Dikirim</option>
                    </select>
                    <select name="batch_id" class="border border-gray-200 bg-white px-2 py-1.5 rounded-lg text-xs">
                        <option value="">Semua Batch</option>
                        <?php while($bt = $all_batches->fetch_assoc()): ?>
                            <option value="<?= $bt['id']; ?>" <?= $filter_batch==$bt['id']?'selected':''; ?>><?= $bt['batch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="bg-gray-900 text-white font-semibold text-xs px-3 py-1.5 rounded-lg">Filter</button>
                </form>

                <!-- Tabel Induk -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-xs text-gray-400 font-semibold uppercase">
                                <th class="pb-3">Kode / Pembeli</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3">No. Resi</th>
                                <th class="pb-3">Total</th>
                                <th class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            <?php while($ord = $orders->fetch_assoc()): ?>
                            <tr>
                                <td class="py-3">
                                    <span class="font-mono font-bold text-indigo-600 block"><?= $ord['order_code']; ?></span>
                                    <span class="text-gray-900 font-medium block"><?= htmlspecialchars($ord['customer_name']); ?></span>
                                </td>
                                <td class="py-3"><span class="px-2 py-0.5 rounded text-xs font-bold <?= $ord['status']=='Dikirim'?'bg-green-50 text-green-600':'bg-amber-50 text-amber-600'; ?>"><?= $ord['status']; ?></span></td>
                                <td class="py-3 font-mono text-xs text-gray-500"><?= !empty($ord['receipt_number']) ? $ord['receipt_number'] : '— Blm Ada Resi'; ?></td>
                                <td class="py-3 font-bold">Rp<?= number_format($ord['total_price'],0,',','.'); ?></td>
                                <td class="py-3 text-right space-x-2">
                                    <a href="orders.php?edit_id=<?= $ord['id']; ?>" class="text-indigo-600 hover:underline">Edit</a>
                                    <button onclick="confirmDeleteOrder(<?= $ord['id']; ?>)" class="text-red-500 hover:underline">Hapus</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Elemen Pagination Navigasi -->
            <div class="flex justify-between items-center border-t border-gray-100 pt-4 mt-6">
                <span class="text-xs text-gray-400">Total data: **<?= $total_data; ?>** data</span>
                <div class="flex space-x-1">
                    <?php for($i=1; $i<=$pages; $i++): ?>
                        <a href="orders.php?page=<?= $i; ?>&status=<?= $filter_status; ?>&batch_id=<?= $filter_batch; ?>" class="px-3 py-1 text-xs rounded-lg font-medium <?= $page == $i ? 'bg-indigo-600 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>"><?= $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Form Edit Data Identitas & Nomor Resi -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 h-fit">
            <h3 class="text-base font-bold text-gray-900 mb-4">Form Pembaruan Berkas Order</h3>
            <?php if($edit_order): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="order_id" value="<?= $edit_order['id']; ?>">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nama Pemesan</label>
                        <input type="text" name="customer_name" value="<?= htmlspecialchars($edit_order['customer_name']); ?>" required class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">No. WhatsApp</label>
                        <input type="text" name="customer_phone" value="<?= htmlspecialchars($edit_order['customer_phone']); ?>" required class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Alamat / NRP</label>
                        <textarea name="customer_address" rows="3" class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none"><?= htmlspecialchars($edit_order['customer_address']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Status Pengiriman</label>
                        <select name="status" class="w-full border border-gray-200 px-3 py-2 rounded-xl bg-white focus:outline-none">
                            <option value="Diproses" <?= $edit_order['status']=='Diproses'?'selected':''; ?>>Diproses</option>
                            <option value="Di-packing" <?= $edit_order['status']=='Di-packing'?'selected':''; ?>>Di-packing</option>
                            <option value="Dikirim" <?= $edit_order['status']=='Dikirim'?'selected':''; ?>>Dikirim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nomor Resi Pengiriman Kurir</label>
                        <input type="text" name="receipt_number" value="<?= htmlspecialchars($edit_order['receipt_number'] ?? ''); ?>" placeholder="Masukkan nomor resi jika ada / dikirim" class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none font-mono text-indigo-600">
                    </div>
                    <button type="submit" name="save_order_edit" class="w-full bg-indigo-600 text-white font-semibold py-2 rounded-xl">Simpan Perubahan</button>
                    <a href="orders.php" class="block text-center text-xs text-gray-400 mt-2 hover:underline">Batal / Reset</a>
                </form>
            <?php else: ?>
                <p class="text-xs text-gray-400 italic">Silakan klik "Edit" pada baris tabel untuk mengubah detail data identitas maba, mengubah status pengerjaan, atau menginput nomor resi ekspedisi.</p>
            <?php endif; ?>
        </div>

    </div>

    <script>
    function confirmDeleteOrder(id) {
        Swal.fire({
            title: 'Hapus Log Transaksi?',
            text: "Data induk beserta sub item belanjaan maba ini akan hilang dari database!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = 'orders.php?delete_id=' + id; }
        });
    }
    </script>
</body>
</html>