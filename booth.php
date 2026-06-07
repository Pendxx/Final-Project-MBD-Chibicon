<?php
// booth.php - Manajemen Tenant & Booth (Bazaar)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Tenant Actions
    if ($action === 'create_tenant' || $action === 'edit_tenant') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $pic = $_POST['pic_name'] ?? '';
        $phone = $_POST['pic_phone'] ?? '';
        $kategori_usaha_id = !empty($_POST['kategori_usaha_id']) ? $_POST['kategori_usaha_id'] : null;
        
        if ($action === 'create_tenant') {
            $stmt = $db->prepare("INSERT INTO Tenant (nama_tenant, kategori_usaha_id, nama_pic, telepon_pic) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $kategori_usaha_id, $pic, $phone]);
            $msg = "Tenant berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Tenant SET nama_tenant=?, kategori_usaha_id=?, nama_pic=?, telepon_pic=? WHERE id=?");
            $stmt->execute([$name, $kategori_usaha_id, $pic, $phone, $id]);
            $msg = "Informasi tenant berhasil diupdate!";
        }
    } elseif ($action === 'delete_tenant') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $db->prepare("DELETE FROM Tenant WHERE id = ?")->execute([$id]);
            $db->prepare("UPDATE Booth SET tenant_id = NULL WHERE tenant_id = ?")->execute([$id]);
            $msg = "Tenant dihapus.";
        }
    }
    
    // Booth Actions
    if ($action === 'create_booth' || $action === 'edit_booth') {
        $old_id = $_POST['id'] ?? null;
        $booth_id = 'B-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); // B-??? randomize
        $name = $_POST['name'] ?? '';
        $price = $_POST['rent_price'] ?? 0;
        $lokasi_booth_id = $_POST['lokasi_booth_id'] ?? null;
        $tenant_id = !empty($_POST['tenant_id']) ? $_POST['tenant_id'] : null;
        
        if ($action === 'create_booth') {
            $stmt = $db->prepare("INSERT INTO Booth (id, nama_booth, harga_sewa, lokasi_booth_id, tenant_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$booth_id, $name, $price, $lokasi_booth_id, $tenant_id]);
            $msg = "Booth berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Booth SET nama_booth=?, harga_sewa=?, lokasi_booth_id=?, tenant_id=? WHERE id=?");
            $stmt->execute([$name, $price, $lokasi_booth_id, $tenant_id, $old_id]);
            $msg = "Informasi booth berhasil diupdate!";
        }
    } elseif ($action === 'delete_booth') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $db->prepare("DELETE FROM Booth WHERE id = ?")->execute([$id]);
            $msg = "Booth dihapus.";
        }
    }
    
    // Check if AJAX Request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $redirect_tab = in_array($action, ['create_booth','edit_booth','delete_booth']) ? 'booth' : 'tenant';
    $_SESSION['toast'] = $msg;
    header("Location: booth.php?tab=" . $redirect_tab);
    exit;
}

// Partial rendering for AJAX table refresh
if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'tenant') {
        $tenants = $db->query("
            SELECT t.*, ku.nama_usaha as category, CONCAT('TN-', LPAD(t.id, 3, '0')) as tenant_code
            FROM Tenant t 
            LEFT JOIN Kategori_Usaha ku ON t.kategori_usaha_id = ku.id
            ORDER BY t.id ASC
        ")->fetchAll();
        foreach($tenants as $tn): ?>
        <tr class="hover:bg-surface-bright transition-colors group">
            <td class="px-6 py-4 font-mono text-on-surface-variant"><?= $tn['tenant_code'] ?></td>
            <td class="px-6 py-4 font-semibold flex items-center gap-3 text-on-surface">
                <div class="w-8 h-8 rounded bg-surface-variant flex items-center justify-center font-bold text-xs uppercase text-on-surface-variant">
                    <?= substr($tn['nama_tenant'], 0, 2) ?>
                </div>
                <?= htmlspecialchars($tn['nama_tenant']) ?>
            </td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-tertiary-fixed text-on-tertiary-fixed"><?= htmlspecialchars($tn['category']) ?></span>
            </td>
            <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($tn['nama_pic']) ?></td>
            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($tn['telepon_pic']) ?></td>
            <td class="px-6 py-4 text-center whitespace-nowrap">
                <button onclick='editTenant(<?= json_encode($tn) ?>)' class="text-primary hover:text-primary-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete_tenant">
                    <input type="hidden" name="id" value="<?= $tn['id'] ?>">
                    <button type="submit" class="text-error hover:text-error-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
    
    if ($_GET['partial'] === 'booth') {
        $booths = $db->query("
            SELECT b.*, t.nama_tenant as tenant_name, lb.nama_lokasi as location 
            FROM Booth b 
            LEFT JOIN Tenant t ON b.tenant_id = t.id 
            LEFT JOIN Lokasi_Booth lb ON b.lokasi_booth_id = lb.id
            ORDER BY b.id ASC
        ")->fetchAll();
        foreach($booths as $b): ?>
        <tr class="hover:bg-surface-bright transition-colors">
            <td class="px-6 py-4 font-mono font-bold text-on-surface"><?= htmlspecialchars($b['id']) ?></td>
            <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($b['nama_booth']) ?></td>
            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($b['location']) ?></td>
            <td class="px-6 py-4 font-medium text-on-surface"><?= format_currency($b['harga_sewa']) ?></td>
            <td class="px-6 py-4 font-semibold text-on-surface"><?= htmlspecialchars($b['tenant_name'] ?? '-') ?></td>
            <td class="px-6 py-4">
                <?php if(!empty($b['tenant_id'])): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold badge-success">Tersewa</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold badge-info">Tersedia</span>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 text-center whitespace-nowrap">
                <button onclick='editBooth(<?= json_encode($b) ?>)' class="bg-surface-container-lowest border border-outline-variant px-2 py-1 rounded hover:bg-surface-container-low text-sm text-on-surface">Edit</button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete_booth">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button type="submit" class="bg-error border border-error text-on-error px-2 py-1 rounded hover:opacity-80 text-sm ml-1">Del</button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
}

// Fetch Data
$tenants = $db->query("
    SELECT t.*, ku.nama_usaha as category, CONCAT('TN-', LPAD(t.id, 3, '0')) as tenant_code
    FROM Tenant t 
    LEFT JOIN Kategori_Usaha ku ON t.kategori_usaha_id = ku.id
    ORDER BY t.id ASC
")->fetchAll();

$booths = $db->query("
    SELECT b.*, t.nama_tenant as tenant_name, lb.nama_lokasi as location 
    FROM Booth b 
    LEFT JOIN Tenant t ON b.tenant_id = t.id 
    LEFT JOIN Lokasi_Booth lb ON b.lokasi_booth_id = lb.id
    ORDER BY b.id ASC
")->fetchAll();

$kategori_usaha = $db->query("SELECT * FROM Kategori_Usaha")->fetchAll();
$lokasi_booth = $db->query("SELECT * FROM Lokasi_Booth")->fetchAll();

$page_title = "Manajemen Bazaar - Chibicon Admin";
$active_menu = "bazaar";
include __DIR__ . '/components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-stack-md">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Bazaar &amp; Tenant</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola data tenant, persewaan booth, dan layout bazaar.</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-outline-variant mb-section-gap flex gap-6">
        <button class="tab-btn active pb-3 font-title-md text-title-md text-primary border-b-2 border-primary transition-all" id="tab-tenant" onclick="switchTab('tenant')">
            Daftar Tenant
        </button>
        <button class="tab-btn pb-3 font-title-md text-title-md text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all" id="tab-booth" onclick="switchTab('booth')">
            Manajemen Booth
        </button>
    </div>

    <!-- TAB CONTENT: TENANT -->
    <div class="tab-content block" id="content-tenant">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <h3 class="font-title-lg text-on-surface">Data Tenant</h3>
                <button onclick="openBoothModal('tenantModal')" class="bg-primary text-on-primary font-label-md px-4 py-2 rounded hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Tenant
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-label-sm uppercase tracking-wider border-b border-outline-variant">
                            <th class="px-6 py-4 font-semibold w-24">ID</th>
                            <th class="px-6 py-4 font-semibold">Nama Tenant / Brand</th>
                            <th class="px-6 py-4 font-semibold">Kategori</th>
                            <th class="px-6 py-4 font-semibold">Nama PIC</th>
                            <th class="px-6 py-4 font-semibold">Telepon PIC</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-tenant" class="font-body-md text-on-surface divide-y divide-outline-variant">
                        <?php foreach($tenants as $tn): ?>
                        <tr class="hover:bg-surface-bright transition-colors group">
                            <td class="px-6 py-4 font-mono text-on-surface-variant"><?= $tn['tenant_code'] ?></td>
                            <td class="px-6 py-4 font-semibold flex items-center gap-3 text-on-surface">
                                <div class="w-8 h-8 rounded bg-surface-variant flex items-center justify-center font-bold text-xs uppercase text-on-surface-variant">
                                    <?= substr($tn['nama_tenant'], 0, 2) ?>
                                </div>
                                <?= htmlspecialchars($tn['nama_tenant']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-tertiary-fixed text-on-tertiary-fixed"><?= htmlspecialchars($tn['category']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($tn['nama_pic']) ?></td>
                            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($tn['telepon_pic']) ?></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <button onclick='editTenant(<?= json_encode($tn) ?>)' class="text-primary hover:text-primary-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete_tenant">
                                    <input type="hidden" name="id" value="<?= $tn['id'] ?>">
                                    <button type="submit" class="text-error hover:text-error-container p-1 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB CONTENT: BOOTH -->
    <div class="tab-content hidden" id="content-booth">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <h3 class="font-title-lg text-on-surface">Daftar Booth</h3>
                <button onclick="openBoothModal('boothModal')" class="bg-primary text-on-primary font-label-md px-4 py-2 rounded hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Booth
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-label-sm uppercase tracking-wider border-b border-outline-variant">
                            <th class="px-6 py-4 font-semibold">Kode</th>
                            <th class="px-6 py-4 font-semibold">Nama Area</th>
                            <th class="px-6 py-4 font-semibold">Lokasi</th>
                            <th class="px-6 py-4 font-semibold">Harga Sewa</th>
                            <th class="px-6 py-4 font-semibold">Penyewa (Tenant)</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-booth" class="font-body-md text-on-surface divide-y divide-outline-variant">
                        <?php foreach($booths as $b): ?>
                        <tr class="hover:bg-surface-bright transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-on-surface"><?= htmlspecialchars($b['id']) ?></td>
                            <td class="px-6 py-4 text-on-surface"><?= htmlspecialchars($b['nama_booth']) ?></td>
                            <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($b['location']) ?></td>
                            <td class="px-6 py-4 font-medium text-on-surface"><?= format_currency($b['harga_sewa']) ?></td>
                            <td class="px-6 py-4 font-semibold text-on-surface"><?= htmlspecialchars($b['tenant_name'] ?? '-') ?></td>
                            <td class="px-6 py-4">
                                <?php if(!empty($b['tenant_id'])): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold badge-success">Tersewa</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold badge-info">Tersedia</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <button onclick='editBooth(<?= json_encode($b) ?>)' class="bg-surface-container-lowest border border-outline-variant px-2 py-1 rounded hover:bg-surface-container-low text-sm text-on-surface">Edit</button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete_booth">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="bg-error border border-error text-on-error px-2 py-1 rounded hover:opacity-80 text-sm ml-1">Del</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div>

<!-- Tenant Modal -->
<div id="tenantModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('tenantModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="tenant">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="tnModalTitle" class="font-title-lg font-bold text-on-surface">Tambah Tenant</h3>
                <button type="button" onclick="closeModal('tenantModal')" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="tnFormAction" value="create_tenant">
                <input type="hidden" name="id" id="tnId">
                <div><label class="block text-sm mb-1 text-on-surface">Nama Tenant / Brand</label><input type="text" name="name" id="tnName" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div>
                    <label class="block text-sm mb-1 text-on-surface">Kategori</label>
                    <select name="kategori_usaha_id" id="tnCat" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                        <?php foreach($kategori_usaha as $ku): ?>
                        <option value="<?= $ku['id'] ?>"><?= htmlspecialchars($ku['nama_usaha']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-sm mb-1 text-on-surface">Nama PIC</label><input type="text" name="pic_name" id="tnPic" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div><label class="block text-sm mb-1 text-on-surface">Telepon PIC</label><input type="text" name="pic_phone" id="tnPhone" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('tenantModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Booth Modal -->
<div id="boothModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('boothModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="booth">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="bModalTitle" class="font-title-lg font-bold text-on-surface">Tambah Booth</h3>
                <button type="button" onclick="closeModal('boothModal')" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="bFormAction" value="create_booth">
                <input type="hidden" name="id" id="bId">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm mb-1 text-on-surface">Lokasi</label>
                        <select name="lokasi_booth_id" id="bLoc" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                            <?php foreach($lokasi_booth as $lb): ?>
                            <option value="<?= $lb['id'] ?>"><?= htmlspecialchars($lb['nama_lokasi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div><label class="block text-sm mb-1 text-on-surface">Nama Area/Booth</label><input type="text" name="name" id="bName" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div><label class="block text-sm mb-1 text-on-surface">Harga Sewa (Rp)</label><input type="number" name="rent_price" id="bPrice" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div>
                    <label class="block text-sm mb-1 text-on-surface">Pilih Tenant (Opsional)</label>
                    <select name="tenant_id" id="bTenant" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                        <option value="">- Kosongkan (Tersedia) -</option>
                        <?php foreach($tenants as $tn): ?>
                            <option value="<?= $tn['id'] ?>"><?= htmlspecialchars($tn['nama_tenant']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('boothModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-primary', 'border-primary');
        btn.classList.add('text-on-surface-variant', 'border-transparent');
    });
    const activeBtn = document.getElementById(`tab-${tabId}`);
    if (!activeBtn) return;
    activeBtn.classList.remove('text-on-surface-variant', 'border-transparent');
    activeBtn.classList.add('text-primary', 'border-primary');
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById(`content-${tabId}`).classList.remove('hidden');
}

// Auto-switch to tab from URL on page load
document.addEventListener('DOMContentLoaded', function() {
    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab) switchTab(tab);

    // ── Bind AJAX Submits ──────────────────────────────────────────────────
    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.ajax-form')) {
            e.preventDefault();
            const form = e.target;
            const targetName = form.dataset.target; // tenant or booth
            ajaxSubmit(form, {
                onSuccess: () => {
                    closeModal(targetName + 'Modal');
                    refreshPartial(`tbody-${targetName}`, targetName);
                    // Also refresh the other tab's table if needed because of relations
                    const otherTarget = targetName === 'tenant' ? 'booth' : 'tenant';
                    refreshPartial(`tbody-${otherTarget}`, otherTarget);
                }
            });
        }
        if (e.target.matches('.delete-form')) {
            e.preventDefault();
            if(!confirm('Hapus data ini?')) return;
            const form = e.target;
            const targetName = form.closest('.tab-content').id.replace('content-', '');
            ajaxSubmit(form, {
                onSuccess: () => {
                    refreshPartial(`tbody-${targetName}`, targetName);
                    const otherTarget = targetName === 'tenant' ? 'booth' : 'tenant';
                    refreshPartial(`tbody-${otherTarget}`, otherTarget);
                }
            });
        }
    });
});

function openBoothModal(id) {
    if (id === 'tenantModal') {
        document.getElementById('tnModalTitle').innerText = 'Tambah Tenant';
        document.getElementById('tnFormAction').value = 'create_tenant';
        document.getElementById('tnId').value = '';
        document.getElementById('tnName').value = '';
        document.getElementById('tnCat').value = '';
        document.getElementById('tnPic').value = '';
        document.getElementById('tnPhone').value = '';
    } else {
        document.getElementById('bModalTitle').innerText = 'Tambah Booth';
        document.getElementById('bFormAction').value = 'create_booth';
        document.getElementById('bId').value = '';
        document.getElementById('bCode').value = '';
        document.getElementById('bName').value = '';
        document.getElementById('bPrice').value = '';
        document.getElementById('bTenant').value = '';
    }
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay')?.classList.remove('opacity-0');
    modal.querySelector('.modal-content')?.classList.remove('scale-95', 'opacity-0');
}

function editTenant(data) {
    document.getElementById('tnModalTitle').innerText = 'Edit Tenant';
    document.getElementById('tnFormAction').value = 'edit_tenant';
    document.getElementById('tnId').value = data.id;
    document.getElementById('tnName').value = data.nama_tenant;
    document.getElementById('tnCat').value = data.kategori_usaha_id;
    document.getElementById('tnPic').value = data.nama_pic;
    document.getElementById('tnPhone').value = data.telepon_pic;
    
    const modal = document.getElementById('tenantModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay').classList.remove('opacity-0');
    modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
}

function editBooth(data) {
    document.getElementById('bModalTitle').innerText = 'Edit Booth';
    document.getElementById('bFormAction').value = 'edit_booth';
    document.getElementById('bId').value = data.id;
    document.getElementById('bName').value = data.nama_booth;
    document.getElementById('bLoc').value = data.lokasi_booth_id;
    document.getElementById('bPrice').value = data.harga_sewa;
    document.getElementById('bTenant').value = data.tenant_id || '';
    
    const modal = document.getElementById('boothModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay').classList.remove('opacity-0');
    modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.querySelector('.modal-overlay').classList.add('opacity-0');
    modal.querySelector('.modal-content').classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); }, 300);
}
</script>
</body>
</html>
