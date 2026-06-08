<?php
// panitia.php - Manajemen Staff & Divisi
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../services/PanitiaService.php';

$panitiaService = new PanitiaService($db);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = $_POST['action'] ?? '';
    $msg = "Aksi tidak dikenal.";

    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['nama_lengkap'] ?? '';
        $phone = $_POST['telepon'] ?? '';
        $divisi_id = $_POST['divisi_id'] ?? null;

        if ($action === 'create') {
            $panitiaService->createPanitia($name, $phone, $divisi_id);
            $msg = "Staff baru berhasil ditambahkan!";
        } else {
            $panitiaService->updatePanitia($id, $name, $phone, $divisi_id);
            $msg = "Data staff berhasil diperbarui!";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $panitiaService->deletePanitia($id);
            $msg = "Data staff dihapus.";
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['toast'] = $msg;
    header("Location: panitia.php");
    exit;
}

// Partial rendering for AJAX
if (isset($_GET['partial']) && $_GET['partial'] === 'staff') {
    $search = $_GET['search'] ?? '';
    $staff = $panitiaService->getAllPanitia($search);
    foreach($staff as $s): ?>
    <tr class="group">
        <td class="text-center font-bold text-[11px] tracking-widest text-on-surface-variant opacity-60"><?= $s['staff_code'] ?></td>
        <td class="font-extrabold text-[#111827]"><?= htmlspecialchars($s['nama_lengkap']) ?></td>
        <td class="font-bold text-on-surface-variant"><?= htmlspecialchars($s['telepon']) ?></td>
        <td>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-primary/10 text-primary">
                <?= htmlspecialchars($s['division']) ?>
            </span>
        </td>
        <td class="text-center">
            <div class="flex items-center justify-center gap-1">
                <button onclick='editStaff(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                </button>
                <form action="" method="POST" class="inline delete-form">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

$search = $_GET['search'] ?? '';
$staff = $panitiaService->getAllPanitia($search);
$divisions = $panitiaService->getDivisions();

$page_title = "Staff & Organization";
$active_menu = "staff";
include __DIR__ . '/../components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto bg-surface">
    <!-- Page Header -->
    <div class="mb-10 animate-fade-in-up">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="rounded-full px-3 py-1 bg-primary/10 text-primary text-[10px] uppercase tracking-[0.2em] font-bold">Internal Organization</span>
                </div>
                <h2 class="font-extrabold text-4xl tracking-tight text-[#111827]">Manajemen Panitia</h2>
                <p class="text-on-surface-variant mt-2 font-medium">Kelola data staf dan pembagian divisi operasional Chibicon.</p>
            </div>
            
            <button onclick="openStaffModal()" class="bg-[#111827] text-white font-bold text-sm px-6 py-3 rounded-2xl hover:bg-black transition-all shadow-soft flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">badge</span>
                Tambah Staff
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar: Divisions Summary -->
        <div class="lg:col-span-1 space-y-6 animate-fade-in-up stagger-1">
            <div class="premium-card-outer">
                <div class="premium-card-inner p-8">
                    <h3 class="font-black text-xs uppercase tracking-[0.2em] text-on-surface-variant mb-6 opacity-40">Distribusi Divisi</h3>
                    <div class="flex flex-col gap-4">
                        <?php foreach($divisions as $div): ?>
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-surface-dim hover:bg-primary/5 transition-colors cursor-default group">
                            <span class="font-bold text-sm text-on-surface group-hover:text-primary transition-colors"><?= htmlspecialchars($div['division']) ?></span>
                            <span class="w-8 h-8 rounded-xl bg-white border border-outline flex items-center justify-center text-xs font-black"><?= $div['count'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table: Staff List -->
        <div class="lg:col-span-3 premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner overflow-hidden flex flex-col">
                <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright">
                    <div class="relative w-full max-w-md">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant opacity-40">search</span>
                        <input id="staffSearch" type="text" placeholder="Cari nama staff..." class="w-full pl-12 pr-4 py-2.5 bg-surface-dim border-transparent rounded-xl text-sm font-medium focus:bg-white focus:ring-4 focus:ring-primary/5 transition-all">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="premium-table" id="staffTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable(0)" class="cursor-pointer group w-24">Kode <span class="sort-icon opacity-20 group-hover:opacity-100 transition-opacity">↕</span></th>
                                <th onclick="sortTable(1)" class="cursor-pointer group">Nama Lengkap <span class="sort-icon opacity-20 group-hover:opacity-100 transition-opacity">↕</span></th>
                                <th>Telepon</th>
                                <th onclick="sortTable(3)" class="cursor-pointer group">Divisi <span class="sort-icon opacity-20 group-hover:opacity-100 transition-opacity">↕</span></th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-staff">
                            <?php foreach($staff as $s): ?>
                            <tr class="group">
                                <td class="text-center font-bold text-[11px] tracking-widest text-on-surface-variant opacity-60"><?= $s['staff_code'] ?></td>
                                <td class="font-extrabold text-[#111827]"><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                <td class="font-bold text-on-surface-variant"><?= htmlspecialchars($s['telepon']) ?></td>
                                <td>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-primary/10 text-primary">
                                        <?= htmlspecialchars($s['division']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='editStaff(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </button>
                                        <form action="" method="POST" class="inline delete-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
            </div>
        </div>
    </div>
</main>

<!-- Staff Modal -->
<div id="staffModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-overlay opacity-0" onclick="closeModal('staffModal')"></div>
    <div class="relative bg-white rounded-3xl shadow-premium w-full max-w-md mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="staff">
            <?php csrf_field(); ?>
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex justify-between items-center">
                <h3 id="modalTitle" class="font-bold text-xl tracking-tight">Formulir Staff</h3>
                <button type="button" onclick="closeModal('staffModal')" class="text-on-surface-variant hover:bg-surface-dim rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-8 flex flex-col gap-5">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="staffId">
                
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama Lengkap</label><input type="text" name="nama_lengkap" id="nameInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">No. Telepon</label><input type="text" name="telepon" id="phoneInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold focus:bg-white transition-all"></div>
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Divisi Penugasan</label>
                    <select name="divisi_id" id="divisiInput" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                        <?php foreach($divisions as $d): ?>
                        <option value="<?= $d['divisi_id'] ?>"><?= htmlspecialchars($d['division']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-end gap-3">
                <button type="button" onclick="closeModal('staffModal')" class="px-6 py-3 border border-outline text-on-surface rounded-xl font-bold text-sm hover:bg-surface-dim transition-all">Batal</button>
                <button type="submit" class="px-6 py-3 bg-[#111827] text-white rounded-xl shadow-soft font-bold text-sm hover:bg-black transition-all">Simpan Staff</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Live search
        const staffSearch = document.getElementById('staffSearch');
        if (staffSearch) {
            staffSearch.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('#staffTable tbody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }

        // AJAX submits
        document.body.addEventListener('submit', function(e) {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                ajaxSubmit(e.target, {
                    onSuccess: () => {
                        closeModal('staffModal');
                        refreshPartial(`tbody-staff`, 'staff');
                    }
                });
            }
            if (e.target.matches('.delete-form')) {
                e.preventDefault();
                if(!confirm('Yakin hapus staff ini?')) return;
                ajaxSubmit(e.target, {
                    onSuccess: () => {
                        refreshPartial(`tbody-staff`, 'staff');
                    }
                });
            }
        });
    });

    function openStaffModal() {
        document.getElementById('modalTitle').innerText = 'Tambah Staff';
        document.getElementById('formAction').value = 'create';
        document.getElementById('staffId').value = '';
        document.getElementById('nameInput').value = '';
        document.getElementById('phoneInput').value = '';
        document.getElementById('divisiInput').value = '';
        openModal('staffModal');
    }

    function editStaff(data) {
        document.getElementById('modalTitle').innerText = 'Edit Staff';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('staffId').value = data.id;
        document.getElementById('nameInput').value = data.nama_lengkap;
        document.getElementById('phoneInput').value = data.telepon;
        document.getElementById('divisiInput').value = data.divisi_id;
        openModal('staffModal');
    }

    // ── Table Sorting ──────────────────────────────────────────────────────────────
    let sortDir = {};
    function sortTable(colIdx) {
        const tbody = document.getElementById('tbody-staff');
        if (!tbody) return;
        const rows  = Array.from(tbody.querySelectorAll('tr'));
        const asc   = !sortDir[colIdx];
        sortDir = {};
        sortDir[colIdx] = asc;

        rows.sort((a, b) => {
            const aCells = a.querySelectorAll('td');
            const bCells = b.querySelectorAll('td');
            if (!aCells[colIdx] || !bCells[colIdx]) return 0;
            const aText = (aCells[colIdx].getAttribute('data-sort') || aCells[colIdx].innerText).trim().toLowerCase();
            const bText = (bCells[colIdx].getAttribute('data-sort') || bCells[colIdx].innerText).trim().toLowerCase();
            return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
        });

        rows.forEach(r => tbody.appendChild(r));

        document.querySelectorAll('.sort-icon').forEach(ic => ic.textContent = '↕');
        const ths = document.querySelectorAll('thead th');
        if (ths[colIdx]) { 
            const ic = ths[colIdx].querySelector('.sort-icon'); 
            if (ic) ic.textContent = asc ? '↑' : '↓'; 
        }
    }
</script>
</body>
</html>
