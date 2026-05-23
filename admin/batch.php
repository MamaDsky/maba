<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

// Proses Aksi Hapus Batch (Delete)
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $db->query("DELETE FROM batches WHERE id = $id");
    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Terhapus!', 'text' => 'Gelombang batch PO berhasil dihapus.'];
    header("Location: batch.php");
    exit;
}

// Proses Simpan Tambah / Edit Batch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_batch'])) {
    $name = htmlspecialchars($_POST['batch_name']);
    $id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;

    if ($id > 0) {
        $db->query("UPDATE batches SET batch_name = '$name' WHERE id = $id");
        $_SESSION['swal'] = ['type' => 'success', 'title' => 'Updated!', 'text' => 'Nama batch berhasil diubah.'];
    } else {
        $db->query("INSERT INTO batches (batch_name, is_active) VALUES ('$name', 0)");
        $_SESSION['swal'] = ['type' => 'success', 'title' => 'Sukses!', 'text' => 'Batch pre-order baru dibuat.'];
    }
    header("Location: batch.php");
    exit;
}

if (isset($_GET['activate_id'])) {
    $id = intval($_GET['activate_id']);
    $db->query("UPDATE batches SET is_active = 0");
    $db->query("UPDATE batches SET is_active = 1 WHERE id = $id");
    header("Location: batch.php");
    exit;
}

// Mengambil data untuk di-edit
$edit_batch = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_batch = $db->query("SELECT * FROM batches WHERE id = $edit_id")->fetch_assoc();
}

$batches = $db->query("SELECT * FROM batches ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manajemen Batch PO</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-8 text-sm">

    <?php if(isset($_SESSION['swal'])): ?>
        <script>Swal.fire({ icon: '<?= $_SESSION['swal']['type']; ?>', title: '<?= $_SESSION['swal']['title']; ?>', text: '<?= $_SESSION['swal']['text']; ?>' });</script>
        <?php unset($_SESSION['swal']); ?>
    <?php endif; ?>

    <div class="max-w-2xl mx-auto mb-4"><a href="index.php" class="text-indigo-600 hover:underline font-bold">← Kembali ke Dashboard Admin</a></div>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl border border-gray-200">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Pengaturan Gelombang Batch Pre-Order</h2>
        
        <form method="POST" class="flex space-x-2 mb-8">
            <?php if($edit_batch): ?><input type="hidden" name="batch_id" value="<?= $edit_batch['id']; ?>"><?php endif; ?>
            <input type="text" name="batch_name" value="<?= htmlspecialchars($edit_batch['batch_name'] ?? ''); ?>" required placeholder="Contoh: Pre-Order Batch 2" class="flex-1 border border-gray-200 px-3 py-2 rounded-xl focus:outline-none">
            <button type="submit" name="save_batch" class="bg-indigo-600 text-white font-semibold px-4 py-2 rounded-xl text-xs"><?= $edit_batch ? 'Update' : 'Tambah'; ?></button>
        </form>

        <div class="space-y-3">
            <?php while($b = $batches->fetch_assoc()): ?>
                <div class="flex justify-between items-center border border-gray-100 p-4 rounded-xl <?= $b['is_active'] ? 'bg-indigo-50/50 border-indigo-200':''; ?>">
                    <div>
                        <p class="font-bold text-gray-800"><?= htmlspecialchars($b['batch_name']); ?></p>
                        <p class="text-xs text-gray-400">Dibuat: <?= date('d M Y', strtotime($b['created_at'])); ?></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php if($b['is_active']): ?>
                            <span class="bg-indigo-600 text-white text-xs px-3 py-1 rounded-full font-bold">Aktif</span>
                        <?php else: ?>
                            <a href="batch.php?activate_id=<?= $b['id']; ?>" class="text-xs text-gray-500 underline hover:text-indigo-600">Buka PO</a>
                        <?php endif; ?>
                        
                        <a href="batch.php?edit_id=<?= $b['id']; ?>" class="text-xs text-indigo-600 hover:underline">Edit</a>
                        <button onclick="confirmDeleteBatch(<?= $b['id']; ?>)" class="text-xs text-red-500 hover:underline">Hapus</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
    function confirmDeleteBatch(id) {
        Swal.fire({
            title: 'Hapus Batch?',
            text: "Batch yang terhapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = 'batch.php?delete_id=' + id; }
        });
    }
    </script>
</body>
</html>