<?php
// pengunjung.php - Manajemen Pengunjung
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $identity = $_POST['identity'] ?? '';
        
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO Pengunjung (nomor_identitas, nama_lengkap, email, telepon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$identity, $name, $email, $phone]);
            $msg = "Pengunjung berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Pengunjung SET nomor_identitas=?, nama_lengkap=?, email=?, telepon=? WHERE id=?");
            $stmt->execute([$identity, $name, $email, $phone, $id]);
            $msg = "Data pengunjung berhasil diupdate!";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM Pengunjung WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Pengunjung berhasil dihapus.";
        }
    }
    
    // Check if AJAX Request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['toast'] = $msg;
    header("Location: pengunjung.php");
    exit;
}

// Partial rendering for AJAX table refresh
if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'visitors') {
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        $where = "";
        $params = [];
        if ($search) {
            $where = "WHERE nama_lengkap LIKE ? OR nomor_identitas LIKE ? OR email LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        $stmt = $db->prepare("SELECT * FROM Pengunjung $where ORDER BY id DESC LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p, PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, (int)$per_page, PDO::PARAM_INT);
        $stmt->bindValue($i,   (int)$offset,   PDO::PARAM_INT);
        $stmt->execute();
        $visitors = $stmt->fetchAll();
        
        foreach ($visitors as $v): ?>
        <tr class="hover:bg-surface-container-high transition-colors group">
            <td class="px-6 py-4 font-medium text-primary">#<?= $v['id'] ?></td>
            <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($v['nomor_identitas']) ?></td>
            <td class="px-6 py-4 font-semibold text-on-surface flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center font-bold text-xs uppercase">
                    <?= substr($v['nama_lengkap'], 0, 2) ?>
                </div>
                <?= htmlspecialchars($v['nama_lengkap']) ?>
            </td>
            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($v['email']) ?></td>
            <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($v['telepon']) ?></td>
            <td class="px-6 py-4 text-on-surface"><?= date('d M Y, H:i', strtotime($v['tgl_wkt_daftar'])) ?></td>
            <td class="px-6 py-4 text-right flex justify-end gap-2">
                <button onclick='editVisitor(<?= json_encode($v) ?>)' class="text-on-surface-variant hover:text-primary transition-colors p-1 rounded">
                    <span class="material-symbols-outlined text-xl">edit</span>
                </button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                    <button type="submit" class="text-on-surface-variant hover:text-error transition-colors p-1 rounded">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        if(empty($visitors)): ?>
        <tr>
            <td colspan="7" class="px-6 py-8 text-center text-on-surface-variant">Data tidak ditemukan.</td>
        </tr>
        <?php endif;
        exit;
    }
}

// Search & Pagination
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = "";
$params = [];
if ($search) {
    $where = "WHERE nama_lengkap LIKE ? OR nomor_identitas LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Count total
$stmt = $db->prepare("SELECT COUNT(*) as total FROM Pengunjung $where");
$stmt->execute($params);
$total_visitors = $stmt->fetch()['total'];
$total_pages = ceil($total_visitors / $per_page);

// Fetch data - bind LIMIT & OFFSET as integers to avoid MySQL syntax error
$stmt = $db->prepare("SELECT * FROM Pengunjung $where ORDER BY id DESC LIMIT ? OFFSET ?");
$i = 1;
foreach ($params as $p) {
    $stmt->bindValue($i++, $p, PDO::PARAM_STR);
}
$stmt->bindValue($i++, (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue($i,   (int)$offset,   PDO::PARAM_INT);
$stmt->execute();
$visitors = $stmt->fetchAll();

// Layout Config
$page_title = "Chibicon Admin - Visitors";
$active_menu = "visitors";
include __DIR__ . '/components/header.php';
?>

<main class="flex-1 p-container-margin md:p-section-gap flex flex-col gap-section-gap">
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="font-headline-lg text-headline-lg font-bold text-on-surface">Manajemen Pengunjung</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola data peserta, validasi identitas, dan monitor pendaftaran acara.</p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <form action="" method="GET" class="relative flex-1 sm:w-64 focus-within:ring-2 focus-within:ring-primary rounded-lg border border-outline-variant bg-surface-container-lowest">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant">search</span>
                <input name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-10 pr-4 py-2.5 bg-transparent border-none focus:ring-0 font-body-md text-body-md text-on-surface placeholder:text-on-surface-variant focus:outline-none" placeholder="Cari ID, Nama..." type="text">
            </form>
            <button onclick="openModal()" class="flex items-center gap-2 bg-primary text-on-primary font-title-md text-title-md px-4 py-2.5 rounded-lg hover:bg-primary-container transition-colors shadow-sm whitespace-nowrap">
                <span class="material-symbols-outlined">add</span> Tambah Pengunjung
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant">
                    <tr>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider">ID</th>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider">No Identitas</th>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider">Nama Lengkap</th>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider">Email</th>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider">Telepon</th>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider">Tanggal Daftar</th>
                        <th class="px-6 py-4 font-label-md text-label-md uppercase text-on-surface-variant font-semibold tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-visitors" class="divide-y divide-outline-variant font-body-md text-body-md text-on-surface">
                    <?php foreach ($visitors as $v): ?>
                    <tr class="hover:bg-surface-container-high transition-colors group">
                        <td class="px-6 py-4 font-medium text-primary">#<?= $v['id'] ?></td>
                        <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($v['nomor_identitas']) ?></td>
                        <td class="px-6 py-4 font-semibold text-on-surface flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center font-bold text-xs uppercase">
                                <?= substr($v['nama_lengkap'], 0, 2) ?>
                            </div>
                            <?= htmlspecialchars($v['nama_lengkap']) ?>
                        </td>
                        <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($v['email']) ?></td>
                        <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($v['telepon']) ?></td>
                        <td class="px-6 py-4 text-on-surface"><?= date('d M Y, H:i', strtotime($v['tgl_wkt_daftar'])) ?></td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            <button onclick='editVisitor(<?= json_encode($v) ?>)' class="text-on-surface-variant hover:text-primary transition-colors p-1 rounded">
                                <span class="material-symbols-outlined text-xl">edit</span>
                            </button>
                            <form action="" method="POST" class="inline delete-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                <button type="submit" class="text-on-surface-variant hover:text-error transition-colors p-1 rounded">
                                    <span class="material-symbols-outlined text-xl">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($visitors)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-on-surface-variant">Data tidak ditemukan.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="bg-surface-container-lowest border-t border-outline-variant px-6 py-4 flex items-center justify-between">
            <div class="font-body-md text-body-md text-on-surface-variant">
                Showing <span class="font-semibold text-on-surface"><?= $total_visitors == 0 ? 0 : $offset + 1 ?>-<?= min($offset + $per_page, $total_visitors) ?></span> of <span class="font-semibold text-on-surface"><?= $total_visitors ?></span> visitors
            </div>
            <div class="flex items-center gap-2">
                <a href="?page=<?= max(1, $page - 1) ?>&search=<?= urlencode($search) ?>" class="flex items-center justify-center w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                    <span class="material-symbols-outlined text-xl">chevron_left</span>
                </a>
                <a href="?page=<?= min($total_pages, $page + 1) ?>&search=<?= urlencode($search) ?>" class="flex items-center justify-center w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors <?= $page >= $total_pages ? 'opacity-50 pointer-events-none' : '' ?>">
                    <span class="material-symbols-outlined text-xl">chevron_right</span>
                </a>
            </div>
        </div>
    </div>
</main>
</div> <!-- Close flex from header -->

<!-- Modal -->
<div id="visitorModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal()"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="visitors">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="modalTitle" class="font-title-lg text-title-lg font-bold text-on-surface">Tambah Pengunjung</h3>
                <button type="button" onclick="closeModal()" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="visitorId">
                
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="nameInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-1">No Identitas (KTP)</label>
                    <input type="text" name="identity" id="identityInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Email</label>
                    <input type="email" name="email" id="emailInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-1">Telepon</label>
                    <input type="text" name="phone" id="phoneInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-outline-variant rounded-lg text-on-surface font-title-md hover:bg-surface-container-low transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg font-title-md hover:bg-primary-container transition-colors shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('visitorModal');
    const overlay = modal.querySelector('.modal-overlay');
    const content = modal.querySelector('.modal-content');
    
    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('submit', function(e) {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                const form = e.target;
                ajaxSubmit(form, {
                    onSuccess: () => {
                        closeModal();
                        refreshPartial(`tbody-visitors`, 'visitors');
                    }
                });
            }
            if (e.target.matches('.delete-form')) {
                e.preventDefault();
                if(!confirm('Yakin hapus pengunjung ini?')) return;
                const form = e.target;
                ajaxSubmit(form, {
                    onSuccess: () => {
                        refreshPartial(`tbody-visitors`, 'visitors');
                    }
                });
            }
        });
    });

    function openModal() {
        document.getElementById('modalTitle').innerText = 'Tambah Pengunjung';
        document.getElementById('formAction').value = 'create';
        document.getElementById('visitorId').value = '';
        document.getElementById('nameInput').value = '';
        document.getElementById('identityInput').value = '';
        document.getElementById('emailInput').value = '';
        document.getElementById('phoneInput').value = '';
        
        modal.classList.remove('hidden');
        // Trigger reflow
        void modal.offsetWidth;
        overlay.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }

    function editVisitor(data) {
        document.getElementById('modalTitle').innerText = 'Edit Pengunjung';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('visitorId').value = data.id;
        document.getElementById('nameInput').value = data.nama_lengkap;
        document.getElementById('identityInput').value = data.nomor_identitas;
        document.getElementById('emailInput').value = data.email;
        document.getElementById('phoneInput').value = data.telepon;
        
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        overlay.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }

    function closeModal() {
        overlay.classList.add('opacity-0');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>

</body>
</html>
