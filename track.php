<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$order = null;
$items = [];

if (isset($_GET['code'])) {
    $code = trim(strtoupper($_GET['code']));
    $stmt = $db->prepare("SELECT o.*, b.batch_name FROM orders o JOIN batches b ON o.batch_id = b.id WHERE o.order_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        $it_stmt = $db->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $it_stmt->bind_param("i", $order['id']);
        $it_stmt->execute();
        $items = $it_stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lacak Pesanan Pre-Order - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 py-12 px-6 text-sm text-gray-800">

    <?php if(isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Pesanan Berhasil Dicatat!',
                text: 'Bukti transfer pembayaran kamu telah kami terima. Admin akan segera memverifikasi pesananmu.',
                customClass: { popup: 'rounded-3xl' }
            });
        </script>
    <?php endif; ?>

    <div class="max-w-xl mx-auto space-y-6">
        
        <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Lacak Status Pesanan Maba</h2>
            <p class="text-gray-400 text-xs mb-4">Masukkan kode transaksi unik unik yang kamu peroleh saat melakukan checkout.</p>
            <form method="GET" class="flex gap-2">
                <input type="text" name="code" value="<?= htmlspecialchars($_GET['code'] ?? ''); ?>" placeholder="Contoh: PO-20260517-XXXX" required class="flex-1 border border-gray-200 px-4 py-2.5 rounded-xl focus:outline-none focus:border-indigo-500 font-mono font-bold uppercase tracking-wide">
                <button type="submit" class="bg-indigo-600 text-white font-bold px-6 py-2.5 rounded-xl hover:bg-indigo-700 transition shadow-xs">Cari</button>
            </form>
        </div>

        <?php if (isset($_GET['code']) && !$order): ?>
            <div class="bg-red-50 border border-red-100 text-red-700 p-4 rounded-xl font-medium text-center">
                Kode transaksi tidak terdaftar. Pastikan susunan kapital dan angka sudah sesuai.
            </div>
        <?php elseif ($order): ?>
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-xs border border-gray-100 space-y-6 animate-in fade-in duration-300">
                
                <div class="flex justify-between items-center border-b border-gray-50 pb-4">
                    <div>
                        <span class="text-xxs font-bold text-gray-400 uppercase tracking-wider block">Status Pengiriman</span>
                        <span class="inline-block mt-1 px-3 py-1 text-xs font-bold rounded-full 
                            <?= $order['status'] == 'Dikirim' ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600'; ?>">
                            📦 <?= $order['status']; ?>
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-xxs font-bold text-gray-400 uppercase tracking-wider block">Kode Pesanan</span>
                        <span class="font-mono font-bold text-gray-900 text-base tracking-wide"><?= $order['order_code']; ?></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs border-b border-gray-50 pb-4">
                    <div>
                        <span class="text-gray-400 block mb-0.5">Nama Lengkap:</span>
                        <p class="font-bold text-gray-900"><?= htmlspecialchars($order['customer_name']); ?></p>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-0.5">Departemen / Jurusan:</span>
                        <p class="font-bold text-gray-900"><?= htmlspecialchars($order['customer_department']); ?></p>
                    </div>
                </div>

                <?php if(!empty($order['receipt_number'])): ?>
                <div class="bg-green-50/70 border border-green-200 p-4 rounded-2xl">
                    <span class="text-xs font-bold text-green-900 block">🚀 Pesanan Sudah Diserahkan ke Kurir!</span>
                    <p class="text-xl font-mono font-black text-green-600 tracking-wide mt-1"><?= $order['receipt_number']; ?></p>
                    <p class="text-xxs text-green-500 mt-1 italic">*Gunakan nomor resi di atas untuk pelacakan ekspedisi reguler kamu.</p>
                </div>
                <?php endif; ?>

                <div>
                    <span class="text-xxs font-bold text-gray-400 uppercase tracking-wider block mb-3">Daftar Item Kelengkapan Maba</span>
                    <div class="space-y-2">
                        <?php while($it = $items->fetch_assoc()): ?>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-gray-700 font-medium"><?= htmlspecialchars($it['name']); ?> <small class="text-gray-400 ml-1">x<?= $it['quantity']; ?></small></span>
                                <span class="font-bold text-gray-900">Rp <?= number_format($it['price'] * $it['quantity'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold text-gray-700 block">Berkas Nota Pembayaran</span>
                        <span class="text-xxs text-gray-400 block mt-0.5">Klik gambar untuk memperbesar nota</span>
                    </div>
                    <div>
                        <?php if(!empty($order['payment_proof'])): ?>
                            <img src="uploads/<?= $order['payment_proof']; ?>" onclick="previewImage(this.src)" class="w-12 h-16 object-cover rounded-lg border bg-gray-50 shadow-3xs cursor-pointer hover:opacity-80 transition">
                        <?php else: ?>
                            <span class="text-xs text-red-500 bg-red-50 border border-red-100 font-medium px-2 py-1 rounded-md">Belum Diupload</span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <script>
    function previewImage(url) {
        Swal.fire({
            title: 'Nota Bukti Pembayaran',
            imageUrl: url,
            imageAlt: 'Bukti Transfer Maba',
            showConfirmButton: false,
            showCloseButton: true,
            customClass: { popup: 'rounded-3xl' }
        });
    }
    </script>
</body>
</html>