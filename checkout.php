<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$cart = $_SESSION['cart'] ?? [];
$total_price = 0;
$items = [];

if (!empty($cart)) {
    $ids = implode(',', array_keys($cart));
    $res = $db->query("SELECT * FROM products WHERE id IN ($ids)");
    while ($row = $res->fetch_assoc()) {
        $row['qty'] = $cart[$row['id']];
        $row['subtotal'] = $row['price'] * $row['qty'];
        $total_price += $row['subtotal'];
        $items[] = $row;
    }
}

// Langkah 1: Handle Submit Form & Refresh Halaman Otomatis
$triggered_code = '';
$triggered_total = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_order'])) {
    $batch_q = $db->query("SELECT id FROM batches WHERE is_active = 1 LIMIT 1");
    $batch_id = ($b = $batch_q->fetch_assoc()) ? $b['id'] : 1;

    $name = htmlspecialchars($_POST['name']);
    $dept = htmlspecialchars($_POST['department']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);
    $order_code = "PO-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -4));

    $stmt = $db->prepare("INSERT INTO orders (order_code, batch_id, customer_name, customer_department, customer_phone, customer_address, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssi", $order_code, $batch_id, $name, $dept, $phone, $address, $total_price);
    
    if ($stmt->execute()) {
        $order_id = $db->insert_id;
        $it_stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $it) {
            $it_stmt->bind_param("iiii", $order_id, $it['id'], $it['qty'], $it['price']);
            $it_stmt->execute();
        }
        $_SESSION['cart'] = [];
        
        $triggered_code = $order_code;
        $triggered_total = $total_price;
    }
}

// Langkah 2: Handle Upload Bukti Pembayaran dari Modal Pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_proof'])) {
    $code = $_POST['order_code'];
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $filename = "PROOF_" . $code . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['proof']['tmp_name'], "uploads/" . $filename)) {
            $db->query("UPDATE orders SET payment_proof = '$filename' WHERE order_code = '$code'");
            echo "<script>
                localStorage.removeItem('pending_order_code');
                localStorage.removeItem('pending_total');
                window.location.href = 'track.php?code=" . $code . "&success=1';
            </script>";
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-[#f4f4f7] py-12 px-4 md:px-8 text-sm text-gray-800 tracking-tight font-sans">

    <div class="max-w-5xl mx-auto flex flex-col gap-6">
        
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-8 rounded-3xl border border-gray-100 shadow-xs gap-4">
            <div>
                <span class="text-xxs font-extrabold uppercase tracking-widest text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md">Secure Checkout</span>
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 mt-2">Penyelesaian Pesanan</h1>
            </div>
            <a href="products.php" class="text-xs font-bold text-gray-400 hover:text-indigo-600 transition flex items-center gap-1">← Kembali ke halaman produk</a>
        </header>

        <?php if(empty($items) && !isset($_POST['submit_order'])): ?>
            <div class="bg-white p-12 rounded-3xl text-center border border-gray-100 shadow-xs">
                <p class="text-gray-400 italic">Keranjang belanja kosong. Silakan pilih produk maba terlebih dahulu.</p>
                <a href="products.php" class="inline-block mt-6 bg-gray-900 hover:bg-gray-800 text-white px-6 py-3 rounded-2xl font-bold text-xs transition">Lihat Katalog Produk</a>
            </div>
        <?php else: ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <form method="POST" class="md:col-span-2 space-y-6">
                    <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-xs space-y-6">
                        <h2 class="text-lg font-extrabold text-gray-900 border-b border-gray-50 pb-3">📋 Informasi Pengiriman & Identitas</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Nama Lengkap</label>
                                <input type="text" name="name" required placeholder="Nama Anda" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Departemen / Jurusan</label>
                                <input type="text" name="department" required placeholder="Contoh: Sistem Informasi" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-all">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Nomor WhatsApp Aktif</label>
                            <input type="text" name="phone" required placeholder="Contoh: 08123456789" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-all">
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Alamat Lengkap / Nomor Kamar Asrama</label>
                            <textarea name="address" required rows="3" placeholder="Tulis alamat kost atau blok asrama secara detail..." class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:bg-white transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <button type="submit" name="submit_order" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-4 rounded-2xl shadow-md transition tracking-wide text-sm">
                        Konfirmasi & Proses Pembayaran →
                    </button>
                </form>

                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between h-fit space-y-6">
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900 border-b border-gray-50 pb-3">🛒 Item Belanjaan</h2>
                        
                        <div class="divide-y divide-gray-50 max-h-60 overflow-y-auto pr-1">
                            <?php foreach($items as $it): ?>
                                <div class="py-3 flex justify-between items-center text-xs">
                                    <div class="max-w-[70%]">
                                        <p class="font-bold text-gray-800 truncate"><?= htmlspecialchars($it['name']); ?></p>
                                        <p class="text-xxs text-gray-400 mt-0.5"><?= $it['qty']; ?>x @ Rp <?= number_format($it['price'], 0, ',', '.'); ?></p>
                                    </div>
                                    <p class="font-bold text-gray-900">Rp <?= number_format($it['subtotal'], 0, ',', '.'); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-2">
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>Subtotal Belanja</span>
                            <span>Rp <?= number_format($total_price, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-50">
                            <span class="text-xs font-bold text-gray-900">Total Tagihan:</span>
                            <span class="text-xl font-black text-indigo-600">Rp <?= number_format($total_price, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <div id="paymentModal" class="fixed inset-0 bg-gray-900/40 z-50 hidden flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl transition-all border border-gray-100">
            
            <div class="bg-gray-900 p-6 text-white text-center">
                <h3 class="text-lg font-black tracking-tight">🔒 Transaksi Terkunci Aman</h3>
                <p class="text-xxs text-gray-400 mt-1 uppercase tracking-widest font-bold">Langkah Terakhir: Verifikasi Pembayaran</p>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-5">
                <input type="hidden" name="order_code" id="modal_order_code">
                
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex justify-between items-center">
                    <div>
                        <span class="text-xxs text-gray-400 font-bold uppercase tracking-wider block">Wajib Ditransfer</span>
                        <span id="modal_total_display" class="text-2xl font-black text-indigo-600"></span>
                    </div>
                    <span class="text-xxs font-extrabold text-amber-600 bg-amber-50 border border-amber-100 px-2 py-1 rounded-md uppercase tracking-wider">Menunggu Bukti</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Bank Transfer</span>
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                            <p class="text-xxs font-bold text-indigo-600 uppercase">Bank Mandiri</p>
                            <p class="text-base font-mono font-black text-gray-900 tracking-wide mt-1">1400-0123-4567</p>
                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">A/N Algatra Digital Agency</p>
                        </div>
                    </div>
                    <div class="space-y-1.5 text-center">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block text-left sm:text-center">Scan QRIS</span>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://algatra.id" class="w-24 h-24 mx-auto rounded-xl border border-gray-100 p-1 bg-white">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Upload Nota / Bukti Pembayaran *</label>
                    <input type="file" name="proof" required accept="image/*" class="w-full text-xs text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                </div>

                <button type="submit" name="upload_proof" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-md transition text-sm">
                    Kirim Bukti Pembayaran Resmi
                </button>
                <p class="text-center text-[10px] text-red-400 font-semibold italic">⚠️ Selesaikan langkah ini. Pembayaran otomatis terkunci di halaman ini jika ditutup.</p>
            </form>
        </div>
    </div>

    <script>
    <?php if(!empty($triggered_code)): ?>
        localStorage.setItem('pending_order_code', '<?= $triggered_code; ?>');
        localStorage.setItem('pending_total', '<?= $triggered_total; ?>');
        window.location.reload(); // Paksa refresh setelah data masuk untuk memutus riwayat POST data form
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', () => {
        const savedCode = localStorage.getItem('pending_order_code');
        const savedTotal = localStorage.getItem('pending_total');

        if (savedCode && savedTotal) {
            document.getElementById('modal_order_code').value = savedCode;
            document.getElementById('modal_total_display').innerText = 'Rp ' + parseInt(savedTotal).toLocaleString('id-ID');
            document.getElementById('paymentModal').classList.remove('hidden');
        }
    });
    </script>
</body>
</html>