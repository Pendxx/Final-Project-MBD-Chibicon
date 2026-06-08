<?php
// pengunjung.php - Manajemen Pengunjung
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../services/PengunjungService.php';

$pengunjungService = new PengunjungService($db);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = $_POST['action'] ?? '';
    $msg = "Aksi tidak dikenal.";

    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $identity = $_POST['nomor_identitas'] ?? '';
        $name = $_POST['nama_lengkap'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['telepon'] ?? '';

        if ($action === 'create') {
            $pengunjungService->createPengunjung($identity, $name, $email, $phone);
            $msg = "Pengunjung berhasil ditambahkan!";
        } else {
            $pengunjungService->updatePengunjung($id, $identity, $name, $email, $phone);
            $msg = "Data pengunjung berhasil diperbarui!";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $pengunjungService->deletePengunjung($id);
            $msg = "Pengunjung dihapus.";
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['toast'] = $msg;
    header("Location: pengunjung.php");
    exit;
}

// Partial rendering for AJAX
if (isset($_GET['partial']) && $_GET['partial'] === 'visitors') {
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    $visitors = $pengunjungService->getAllPengunjung($search, $per_page, $offset);
    foreach($visitors as $p): ?>
    <tr class="group">
        <td class="font-extrabold text-[#111827]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-black text-sm uppercase">
                    <?= substr($p['nama_lengkap'], 0, 1) ?>
                </div>
                <?= htmlspecialchars($p['nama_lengkap']) ?>
            </div>
        </td>
        <td class="font-bold text-on-surface-variant text-xs tracking-widest uppercase opacity-60"><?= htmlspecialchars($p['nomor_identitas']) ?></td>
        <td class="font-medium text-on-surface"><?= htmlspecialchars($p['email']) ?></td>
        <td class="text-sm font-bold text-on-surface-variant"><?= htmlspecialchars($p['telepon']) ?></td>
        <td class="text-center">
            <div class="flex items-center justify-center gap-1">
                <button onclick='editVisitor(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                </button>
                <form action="" method="POST" class="inline delete-form">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="w-9 h-9 rounded-xl hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90 border border-error/20">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach;
    exit;
}

// Search & Pagination
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;
$visitors = $pengunjungService->getAllPengunjung($search, $per_page, $offset);
$total_rows = $pengunjungService->countPengunjung($search);
$total_pages = ceil($total_rows / $per_page);

$page_title = "Database Pengunjung";
$active_menu = "visitors";
include __DIR__ . '/../components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto bg-surface">
    <!-- Page Header -->
    <div class="mb-10 animate-fade-in-up">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="rounded-full px-3 py-1 bg-primary/10 text-primary text-[10px] uppercase tracking-[0.2em] font-bold">CRM & Attendance</span>
                </div>
                <h2 class="font-extrabold text-4xl tracking-tight text-[#111827]">Daftar Pengunjung</h2>
                <p class="text-on-surface-variant mt-2 font-medium">Kelola database pendaftaran pengunjung Chibicon.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="openVisitorModal()" class="bg-primary text-white font-bold text-sm px-6 py-3 rounded-2xl hover:bg-primary-hover transition-all shadow-soft flex items-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">person_add</span>
                    Tambah Pengunjung
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="premium-card-outer animate-fade-in-up stagger-1">
        <div class="premium-card-inner overflow-hidden flex flex-col">
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex flex-col md:flex-row justify-between items-center gap-4">
                <form action="" method="GET" class="relative w-full max-w-md">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant opacity-40">search</span>
                    <input name="search" value="<?= htmlspecialchars($search) ?>" type="text" placeholder="Cari nama atau email..." class="w-full pl-12 pr-4 py-2.5 bg-surface-dim border-transparent rounded-xl text-sm font-medium focus:bg-white focus:ring-4 focus:ring-primary/5 transition-all">
                </form>
                <div class="text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">
                    Showing <span class="text-primary"><?= count($visitors) ?></span> of <?= $total_rows ?>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>No. Identitas</th>
                            <th>Alamat Email</th>
                            <th>No. Telepon</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-visitors">
                        <?php foreach($visitors as $p): ?>
                        <tr class="group">
                            <td class="font-extrabold text-[#111827]">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-black text-sm uppercase">
                                        <?= substr($p['nama_lengkap'], 0, 1) ?>
                                    </div>
                                    <?= htmlspecialchars($p['nama_lengkap']) ?>
                                </div>
                            </td>
                            <td class="font-bold text-on-surface-variant text-xs tracking-widest uppercase opacity-60"><?= htmlspecialchars($p['nomor_identitas']) ?></td>
                            <td class="font-medium text-on-surface"><?= htmlspecialchars($p['email']) ?></td>
                            <td class="text-sm font-bold text-on-surface-variant"><?= htmlspecialchars($p['telepon']) ?></td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick='editVisitor(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <form action="" method="POST" class="inline delete-form">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="w-9 h-9 rounded-xl hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90 border border-error/20">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-center gap-2">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all <?= $i === $page ? 'bg-primary text-white shadow-soft' : 'bg-white border border-outline text-on-surface-variant hover:bg-surface-dim' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Visitor Modal -->
<div id="visitorModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-overlay opacity-0" onclick="closeModal('visitorModal')"></div>
    <div class="relative bg-white rounded-3xl shadow-premium w-full max-w-md mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="visitors">
            <?php csrf_field(); ?>
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex justify-between items-center">
                <h3 id="modalTitle" class="font-bold text-xl tracking-tight">Form Pengunjung</h3>
                <button type="button" onclick="closeModal('visitorModal')" class="text-on-surface-variant hover:bg-surface-dim rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-8 flex flex-col gap-5">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="visitorId">
                
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama Lengkap</label><input type="text" name="nama_lengkap" id="nameInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">No. Identitas (KTP/Kartu Pelajar)</label><input type="text" name="nomor_identitas" id="identityInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold focus:bg-white transition-all"></div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Alamat Email</label><input type="email" name="email" id="emailInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">No. Telepon / WhatsApp</label><input type="text" name="telepon" id="phoneInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
            </div>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-end gap-3">
                <button type="button" onclick="closeModal('visitorModal')" class="px-6 py-3 border border-outline text-on-surface rounded-xl font-bold text-sm hover:bg-surface-dim transition-all">Batal</button>
                <button type="submit" class="px-6 py-3 bg-[#111827] text-white rounded-xl shadow-soft font-bold text-sm hover:bg-black transition-all">Simpan Pengunjung</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('submit', function(e) {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                const form = e.target;
                ajaxSubmit(form, {
                    onSuccess: () => {
                        closeModal('visitorModal');
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

    function openVisitorModal() {
        document.getElementById('modalTitle').innerText = 'Tambah Pengunjung';
        document.getElementById('formAction').value = 'create';
        document.getElementById('visitorId').value = '';
        document.getElementById('nameInput').value = '';
        document.getElementById('identityInput').value = '';
        document.getElementById('emailInput').value = '';
        document.getElementById('phoneInput').value = '';
        openModal('visitorModal');
    }

    function editVisitor(data) {
        document.getElementById('modalTitle').innerText = 'Edit Pengunjung';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('visitorId').value = data.id;
        document.getElementById('nameInput').value = data.nama_lengkap;
        document.getElementById('identityInput').value = data.nomor_identitas;
        document.getElementById('emailInput').value = data.email;
        document.getElementById('phoneInput').value = data.telepon;
        openModal('visitorModal');
    }
</script>
</body>
</html>
