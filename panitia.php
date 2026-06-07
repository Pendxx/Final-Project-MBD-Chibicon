<?php
// panitia.php - Manajemen Panitia & Staff
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $divisi_id = $_POST['divisi_id'] ?? null;
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO Panitia (nama_lengkap, telepon, divisi_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $phone, $divisi_id]);
            $msg = "Panitia berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Panitia SET nama_lengkap=?, telepon=?, divisi_id=? WHERE id=?");
            $stmt->execute([$name, $phone, $divisi_id, $id]);
            $msg = "Data panitia berhasil diupdate!";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM Panitia WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Panitia berhasil dihapus.";
        }
    }
    
    // Check if AJAX Request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['toast'] = $msg;
    header("Location: panitia.php");
    exit;
}

// Partial rendering for AJAX table refresh
if (isset($_GET['partial']) && $_GET['partial'] === 'staff') {
    $search = $_GET['search'] ?? '';
    $where = "";
    $params = [];
    if ($search) {
        $where = "WHERE p.nama_lengkap LIKE ?";
        $params = ["%$search%"];
    }
    $stmt = $db->prepare("
        SELECT p.*, d.nama_divisi as division, CONCAT('STF-', LPAD(p.id, 4, '0')) as staff_code 
        FROM Panitia p 
        LEFT JOIN Divisi d ON p.divisi_id = d.id 
        $where ORDER BY p.nama_lengkap ASC
    ");
    $stmt->execute($params);
    $staff_list = $stmt->fetchAll();
    
    foreach($staff_list as $st): ?>
    <tr class="hover:bg-surface-bright transition-colors group">
        <td class="px-6 py-4 font-mono text-on-surface-variant" data-sort="<?= htmlspecialchars($st['staff_code']) ?>"><?= $st['staff_code'] ?></td>
        <td class="px-6 py-4 font-semibold flex items-center gap-3 text-on-surface" data-sort="<?= htmlspecialchars($st['nama_lengkap']) ?>">
            <div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-bold text-xs uppercase">
                <?= substr($st['nama_lengkap'], 0, 2) ?>
            </div>
            <?= htmlspecialchars($st['nama_lengkap']) ?>
        </td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-surface-container-highest border border-outline-variant text-on-surface"><?= htmlspecialchars($st['division']) ?></span>
        </td>
        <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($st['telepon']) ?></td>
        <td class="px-6 py-4 text-center whitespace-nowrap">
            <button onclick='editStaff(<?= json_encode($st) ?>)' class="text-primary hover:text-primary-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
            <form action="" method="POST" class="inline delete-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $st['id'] ?>">
                <button type="submit" class="text-error hover:text-error-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
            </form>
        </td>
    </tr>
    <?php endforeach; 
    if(empty($staff_list)): ?>
    <tr><td colspan="6" class="px-6 py-8 text-center text-on-surface-variant">Tidak ada data staff ditemukan.</td></tr>
    <?php endif;
    exit;
}

// Search
$search = $_GET['search'] ?? '';
$where = "";
$params = [];
if ($search) {
    $where = "WHERE p.nama_lengkap LIKE ?";
    $params = ["%$search%"];
}

// Fetch Staff Data
$stmt = $db->prepare("
    SELECT p.*, d.nama_divisi as division, CONCAT('STF-', LPAD(p.id, 4, '0')) as staff_code 
    FROM Panitia p 
    LEFT JOIN Divisi d ON p.divisi_id = d.id 
    $where ORDER BY p.nama_lengkap ASC
");
$stmt->execute($params);
$staff_list = $stmt->fetchAll();

// Fetch Divisions Summary
$divisions = $db->query("
    SELECT d.nama_divisi as division, d.id as divisi_id, COUNT(p.id) as count 
    FROM Divisi d 
    LEFT JOIN Panitia p ON d.id = p.divisi_id 
    GROUP BY d.id
")->fetchAll();

$page_title = "Manajemen Panitia - Chibicon Admin";
$active_menu = "staff";
include __DIR__ . '/components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto flex gap-6">
    <!-- Main Content (Left) -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-stack-md">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Manajemen Panitia</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola data panitia, divisi, dan status aktif selama acara.</p>
            </div>
            <button onclick="openPanitiaModal()" class="bg-primary text-on-primary font-label-md px-4 py-2.5 rounded-lg hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2 whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">person_add</span> Tambah Staff
            </button>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-wrap gap-4 items-center justify-between mb-6">
            <form action="" method="GET" class="relative w-full sm:w-72">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                <input name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-10 pr-4 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary text-body-md placeholder:text-on-surface-variant" placeholder="Cari nama atau kode staff..." type="text">
            </form>
            <div class="flex gap-2">
                <select id="divisionFilter" class="py-2 px-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface text-body-md focus:outline-none focus:border-primary">
                    <option value="">Semua Divisi</option>
                    <?php foreach($divisions as $div): ?>
                        <option><?= htmlspecialchars($div['division']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-label-sm uppercase tracking-wider border-b border-outline-variant">
                            <th class="px-6 py-4 font-semibold w-24 cursor-pointer select-none hover:bg-surface-container" onclick="sortTable(0)">
                                ID <span class="sort-icon text-[10px]">↕</span>
                            </th>
                            <th class="px-6 py-4 font-semibold cursor-pointer select-none hover:bg-surface-container" onclick="sortTable(1)">
                                Nama Lengkap <span class="sort-icon text-[10px]">↕</span>
                            </th>
                            <th class="px-6 py-4 font-semibold cursor-pointer select-none hover:bg-surface-container" onclick="sortTable(2)">
                                Divisi <span class="sort-icon text-[10px]">↕</span>
                            </th>
                            <th class="px-6 py-4 font-semibold">Telepon</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-staff" class="font-body-md text-on-surface divide-y divide-outline-variant">
                        <?php foreach($staff_list as $st): ?>
                        <tr class="hover:bg-surface-bright transition-colors group">
                            <td class="px-6 py-4 font-mono text-on-surface-variant" data-sort="<?= htmlspecialchars($st['staff_code']) ?>"><?= $st['staff_code'] ?></td>
                            <td class="px-6 py-4 font-semibold flex items-center gap-3 text-on-surface" data-sort="<?= htmlspecialchars($st['nama_lengkap']) ?>">
                                <div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-bold text-xs uppercase">
                                    <?= substr($st['nama_lengkap'], 0, 2) ?>
                                </div>
                                <?= htmlspecialchars($st['nama_lengkap']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-surface-container-highest border border-outline-variant text-on-surface"><?= htmlspecialchars($st['division']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($st['telepon']) ?></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <button onclick='editStaff(<?= json_encode($st) ?>)' class="text-primary hover:text-primary-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $st['id'] ?>">
                                    <button type="submit" class="text-error hover:text-error-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($staff_list)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-on-surface-variant">Tidak ada data staff ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Aside Summary (Right) -->
    <aside class="hidden xl:flex w-72 flex-col gap-6">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-card-padding shadow-sm">
            <h3 class="font-title-lg text-on-surface font-bold mb-4">Ringkasan Divisi</h3>
            <div class="flex flex-col gap-3">
                <?php foreach($divisions as $div): ?>
                <div class="flex items-center justify-between">
                    <span class="font-body-md text-on-surface-variant flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span> <?= htmlspecialchars($div['division']) ?>
                    </span>
                    <span class="font-semibold text-on-surface bg-surface-container-high px-2 py-0.5 rounded-md text-sm"><?= $div['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-primary text-on-primary rounded-xl p-card-padding shadow-sm relative overflow-hidden">
            <div class="absolute right-[-20px] top-[-20px] opacity-10">
                <span class="material-symbols-outlined text-[100px]">campaign</span>
            </div>
            <h3 class="font-title-lg font-bold mb-2 relative z-10">Broadcast Panitia</h3>
            <p class="font-body-sm mb-4 relative z-10 opacity-90">Kirim pesan massal ke seluruh staff aktif.</p>
            <button onclick="openBroadcast()" class="w-full bg-on-primary text-primary font-label-md py-2 rounded-lg font-bold hover:bg-surface-container-lowest transition-colors relative z-10">
                Tulis Pesan Baru
            </button>
        </div>
    </aside>
</main>
</div>

<!-- Modal -->
<div id="staffModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('staffModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="staff">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="modalTitle" class="font-title-lg font-bold text-on-surface">Tambah Staff</h3>
                <button type="button" onclick="closeModal('staffModal')" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="staffId">
                <div><label class="block text-sm mb-1 text-on-surface">Nama Lengkap</label><input type="text" name="name" id="nameInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div><label class="block text-sm mb-1 text-on-surface">Telepon</label><input type="text" name="phone" id="phoneInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div>
                    <label class="block text-sm mb-1 text-on-surface">Divisi</label>
                    <select name="divisi_id" id="divInput" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                        <?php foreach($divisions as $div): ?>
                        <option value="<?= $div['divisi_id'] ?>"><?= htmlspecialchars($div['division']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('staffModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Broadcast Modal -->
<div id="broadcastModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeBroadcast()"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-lg mx-4 modal-content scale-95 opacity-0">
        <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary">campaign</span>
                <h3 class="font-title-lg font-bold text-on-surface">Broadcast ke Seluruh Staff</h3>
            </div>
            <button type="button" onclick="closeBroadcast()" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div class="bg-secondary-fixed/30 border border-outline-variant rounded-lg px-4 py-2 flex items-center gap-2 text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-[16px]">info</span>
                Pesan akan dikirim ke <strong class="text-on-surface"><?= count($staff_list) ?></strong> panitia.
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Judul Pesan</label>
                <input id="bcSubject" type="text" placeholder="Contoh: Pengumuman Jadwal Briefing" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Isi Pesan</label>
                <textarea id="bcMessage" rows="5" placeholder="Tulis pesan broadcast di sini..." class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary resize-none"></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
            <button type="button" onclick="closeBroadcast()" class="px-4 py-2 border border-outline-variant rounded-lg">Batal</button>
            <button type="button" onclick="sendBroadcast()" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">send</span> Kirim Pesan
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Bind AJAX Submits ──────────────────────────────────────────────────
    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.ajax-form')) {
            e.preventDefault();
            const form = e.target;
            ajaxSubmit(form, {
                onSuccess: () => {
                    closeModal('staffModal');
                    refreshPartial(`tbody-staff`, 'staff');
                }
            });
        }
        if (e.target.matches('.delete-form')) {
            e.preventDefault();
            if(!confirm('Hapus staff ini?')) return;
            ajaxSubmit(e.target, {
                onSuccess: () => {
                    refreshPartial(`tbody-staff`, 'staff');
                }
            });
        }
    });

    // ── Division Filter ────────────────────────────────────────────────────────────
    const divFilter = document.getElementById('divisionFilter');
    if (divFilter) {
        divFilter.addEventListener('change', function() {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#tbody-staff tr').forEach(row => {
                if (!val) { row.style.display = ''; return; }
                const divCell = row.querySelectorAll('td')[2];
                row.style.display = divCell && divCell.innerText.trim().toLowerCase() === val ? '' : 'none';
            });
        });
    }
});

// ── Staff Modal ────────────────────────────────────────────────────────────────
function openPanitiaModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Staff';
    document.getElementById('formAction').value = 'create';
    document.getElementById('staffId').value = '';
    document.getElementById('nameInput').value = '';
    document.getElementById('phoneInput').value = '';
    document.getElementById('divInput').value = '';
    document.getElementById('statusInput').value = 'Active';
    openModal('staffModal'); // use app.js's generic openModal
}

function editStaff(data) {
    document.getElementById('modalTitle').innerText = 'Edit Staff';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('staffId').value = data.id;
    document.getElementById('nameInput').value = data.nama_lengkap;
    document.getElementById('phoneInput').value = data.telepon;
    document.getElementById('divInput').value = data.divisi_id;
    openModal('staffModal');
}

// ── Broadcast Modal ────────────────────────────────────────────────────────────
function openBroadcast() {
    document.getElementById('bcSubject').value = '';
    document.getElementById('bcMessage').value = '';
    openModal('broadcastModal'); // use app.js generic
}

function closeBroadcast() {
    closeModal('broadcastModal');
}

function sendBroadcast() {
    const subj = document.getElementById('bcSubject').value.trim();
    const msg  = document.getElementById('bcMessage').value.trim();
    if (!subj || !msg) { alert('Judul dan isi pesan tidak boleh kosong.'); return; }
    closeBroadcast();
    showToast('Broadcast "' + subj + '" berhasil dikirim!', 'success');
    
    // Reset "read all" so new notification persists across pages
    localStorage.removeItem('notifCleared');

    // Push notification to Top Bar
    const notifList = document.getElementById('notifList');
    if (notifList) {
        notifList.classList.remove('hidden');
        document.getElementById('notifEmpty')?.classList.add('hidden');
        const notifItem = document.createElement('div');
        notifItem.className = 'px-4 py-3 flex gap-3 items-start hover:bg-surface-container-low cursor-pointer transition-colors';
        notifItem.setAttribute('data-notif', '');
        notifItem.innerHTML = `
            <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">campaign</span>
            <div><div class="text-sm font-medium text-on-surface">Broadcast Dikirim</div><div class="text-xs text-on-surface-variant mt-0.5">${subj}</div></div>
        `;
        notifList.prepend(notifItem);
        const dot = document.getElementById('notifDot');
        if (dot) { dot.style.display = ''; dot.classList.remove('hidden'); }
    }
}

// ── Table Sorting ──────────────────────────────────────────────────────────────
let sortDir = {};
function sortTable(colIdx) {
    const tbody = document.getElementById('tbody-staff');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    const asc   = !sortDir[colIdx];
    sortDir = {};
    sortDir[colIdx] = asc;

    rows.sort((a, b) => {
        const aCell = a.querySelectorAll('td')[colIdx];
        const bCell = b.querySelectorAll('td')[colIdx];
        if (!aCell || !bCell) return 0;
        const aText = (aCell.getAttribute('data-sort') || aCell.innerText).trim().toLowerCase();
        const bText = (bCell.getAttribute('data-sort') || bCell.innerText).trim().toLowerCase();
        return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });

    rows.forEach(r => tbody.appendChild(r));

    document.querySelectorAll('.sort-icon').forEach(ic => ic.textContent = '↕');
    const th = document.querySelectorAll('thead th')[colIdx];
    if (th) { const ic = th.querySelector('.sort-icon'); if (ic) ic.textContent = asc ? '↑' : '↓'; }
}
</script>
</body>
</html>
