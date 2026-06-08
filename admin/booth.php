<?php
// booth.php - Manajemen Tenant & Booth (Bazaar)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../services/BoothService.php';

$boothService = new BoothService($db);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = $_POST['action'] ?? '';
    
    // Tenant Actions
    if ($action === 'create_tenant' || $action === 'edit_tenant') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $pic = $_POST['pic_name'] ?? '';
        $phone = $_POST['pic_phone'] ?? '';
        $kategori_usaha_id = !empty($_POST['kategori_usaha_id']) ? $_POST['kategori_usaha_id'] : null;
        
        if ($action === 'create_tenant') {
            $boothService->createTenant($name, $kategori_usaha_id, $pic, $phone);
            $msg = "Tenant berhasil ditambahkan!";
        } else {
            $boothService->updateTenant($id, $name, $kategori_usaha_id, $pic, $phone);
            $msg = "Informasi tenant berhasil diupdate!";
        }
    } elseif ($action === 'delete_tenant') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $boothService->deleteTenant($id);
            $msg = "Tenant dihapus.";
        }
    }
    
    // Booth Actions
    if ($action === 'create_booth' || $action === 'edit_booth') {
        $old_id = $_POST['id'] ?? null;
        $booth_id = $old_id ?? ('B-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)); 
        $name = $_POST['name'] ?? '';
        $price = $_POST['rent_price'] ?? 0;
        $lokasi_booth_id = $_POST['lokasi_booth_id'] ?? null;
        $tenant_id = !empty($_POST['tenant_id']) ? $_POST['tenant_id'] : null;
        
        if ($action === 'create_booth') {
            $boothService->createBooth($booth_id, $name, $price, $lokasi_booth_id, $tenant_id);
            $msg = "Booth berhasil ditambahkan!";
        } else {
            $boothService->updateBooth($old_id, $name, $price, $lokasi_booth_id, $tenant_id);
            $msg = "Informasi booth berhasil diupdate!";
        }
    } elseif ($action === 'delete_booth') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $boothService->deleteBooth($id);
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
        $tenants = $boothService->getAllTenants();
        foreach($tenants as $tn): ?>
        <tr class="group">
            <td class="text-center font-bold text-[11px] tracking-widest text-on-surface-variant opacity-60"><?= $tn['tenant_code'] ?></td>
            <td class="font-extrabold text-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs uppercase">
                        <?= substr($tn['nama_tenant'], 0, 2) ?>
                    </div>
                    <?= htmlspecialchars($tn['nama_tenant']) ?>
                </div>
            </td>
            <td>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-tertiary-container/30 text-on-tertiary-container"><?= htmlspecialchars($tn['category']) ?></span>
            </td>
            <td class="font-semibold text-on-surface"><?= htmlspecialchars($tn['nama_pic']) ?></td>
            <td class="text-sm font-medium text-on-surface-variant"><?= htmlspecialchars($tn['telepon_pic']) ?></td>
            <td class="text-center">
                <div class="flex items-center justify-center gap-1">
                    <button onclick='editTenant(<?= htmlspecialchars(json_encode($tn), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                    </button>
                    <form action="" method="POST" class="inline delete-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_tenant">
                        <input type="hidden" name="id" value="<?= $tn['id'] ?>">
                        <button type="submit" class="w-9 h-9 rounded-xl hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
    
    if ($_GET['partial'] === 'booth') {
        $booths = $boothService->getAllBooths();
        foreach($booths as $b): ?>
        <tr class="group">
            <td class="font-bold text-primary"><?= htmlspecialchars($b['id']) ?></td>
            <td class="font-extrabold text-[#111827]"><?= htmlspecialchars($b['nama_booth']) ?></td>
            <td>
                <span class="inline-flex items-center gap-1 text-sm font-semibold text-on-surface-variant">
                    <span class="material-symbols-outlined text-primary text-[16px]">location_on</span>
                    <?= htmlspecialchars($b['location']) ?>
                </span>
            </td>
            <td class="font-bold text-[#111827]"><?= format_currency($b['harga_sewa']) ?></td>
            <td class="font-semibold text-on-surface"><?= htmlspecialchars($b['tenant_name'] ?? '-') ?></td>
            <td>
                <?php if(!empty($b['tenant_id'])): ?>
                    <span class="px-3 py-1 rounded-full badge-success text-[10px] font-extrabold uppercase tracking-wider">Tersewa</span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full badge-info text-[10px] font-extrabold uppercase tracking-wider">Tersedia</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <div class="flex items-center justify-center gap-1">
                    <button onclick='editBooth(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)' class="w-8 h-8 rounded-lg hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                    <form action="" method="POST" class="inline delete-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_booth">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="w-8 h-8 rounded-lg hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90 border border-error/20">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
}

// Fetch Data
$tenants = $boothService->getAllTenants();
$booths = $boothService->getAllBooths();
$kategori_usaha = $boothService->getCategories();
$lokasi_booth = $boothService->getLocations();

$page_title = "Bazaar & Tenant";
$active_menu = "bazaar";
include __DIR__ . '/../components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto bg-surface">
    <!-- Page Header -->
    <div class="mb-10 animate-fade-in-up">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="rounded-full px-3 py-1 bg-primary/10 text-primary text-[10px] uppercase tracking-[0.2em] font-bold">Bazaar Management</span>
                </div>
                <h2 class="font-extrabold text-4xl tracking-tight text-[#111827]">Bazaar &amp; Booth</h2>
                <p class="text-on-surface-variant mt-2 font-medium">Kelola penempatan tenant dan inventaris booth bazaar Chibicon.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="openBoothModal('tenantModal')" class="bg-[#111827] text-white font-bold text-sm px-6 py-3 rounded-2xl hover:bg-black transition-all shadow-soft flex items-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                    Tambah Tenant
                </button>
                <button onclick="openBoothModal('boothModal')" class="bg-primary text-white font-bold text-sm px-6 py-3 rounded-2xl hover:bg-primary-hover transition-all shadow-soft flex items-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                    Tambah Booth
                </button>
            </div>
        </div>
    </div>

    <!-- Modern Tab Navigation -->
    <div class="flex p-1.5 bg-surface-dim rounded-2xl w-max border border-outline-variant mb-10 animate-fade-in-up stagger-1">
        <button onclick="switchTab('tenant')" id="tab-tenant" class="tab-btn active px-8 py-2.5 rounded-xl font-bold text-sm transition-all duration-500 ease-premium">
            Daftar Tenant
        </button>
        <button onclick="switchTab('booth')" id="tab-booth" class="tab-btn px-8 py-2.5 rounded-xl font-bold text-sm text-on-surface-variant hover:text-primary transition-all duration-500 ease-premium">
            Manajemen Booth
        </button>
    </div>

    <!-- TAB CONTENT: TENANT -->
    <div class="tab-content block" id="content-tenant">
        <div class="premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th class="w-24 text-center">Kode</th>
                                <th>Nama Tenant / Brand</th>
                                <th>Kategori</th>
                                <th>Nama PIC</th>
                                <th>Telepon PIC</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-tenant">
                            <?php foreach($tenants as $tn): ?>
                            <tr class="group">
                                <td class="text-center font-bold text-[11px] tracking-widest text-on-surface-variant opacity-60"><?= $tn['tenant_code'] ?></td>
                                <td class="font-extrabold text-[#111827]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs uppercase">
                                            <?= substr($tn['nama_tenant'], 0, 2) ?>
                                        </div>
                                        <?= htmlspecialchars($tn['nama_tenant']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-tertiary-container/30 text-on-tertiary-container"><?= htmlspecialchars($tn['category']) ?></span>
                                </td>
                                <td class="font-semibold text-on-surface"><?= htmlspecialchars($tn['nama_pic']) ?></td>
                                <td class="text-sm font-medium text-on-surface-variant"><?= htmlspecialchars($tn['telepon_pic']) ?></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='editTenant(<?= htmlspecialchars(json_encode($tn), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </button>
                                        <form action="" method="POST" class="inline delete-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_tenant">
                                            <input type="hidden" name="id" value="<?= $tn['id'] ?>">
                                            <button type="submit" class="w-9 h-9 rounded-xl hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90">
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

    <!-- TAB CONTENT: BOOTH -->
    <div class="tab-content hidden" id="content-booth">
        <div class="premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Area</th>
                                <th>Lokasi</th>
                                <th>Harga Sewa</th>
                                <th>Penyewa (Tenant)</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-booth">
                            <?php foreach($booths as $b): ?>
                            <tr class="group">
                                <td class="font-bold text-primary"><?= htmlspecialchars($b['id']) ?></td>
                                <td class="font-extrabold text-[#111827]"><?= htmlspecialchars($b['nama_booth']) ?></td>
                                <td>
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[16px]">location_on</span>
                                        <?= htmlspecialchars($b['location']) ?>
                                    </span>
                                </td>
                                <td class="font-bold text-[#111827]"><?= format_currency($b['harga_sewa']) ?></td>
                                <td class="font-semibold text-on-surface"><?= htmlspecialchars($b['tenant_name'] ?? '-') ?></td>
                                <td>
                                    <?php if(!empty($b['tenant_id'])): ?>
                                        <span class="px-3 py-1 rounded-full badge-success text-[10px] font-extrabold uppercase tracking-wider">Tersewa</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full badge-info text-[10px] font-extrabold uppercase tracking-wider">Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='editBooth(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)' class="w-8 h-8 rounded-lg hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <form action="" method="POST" class="inline delete-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_booth">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90 border border-error/20">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
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

<!-- Modals -->
<div id="tenantModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-overlay opacity-0" onclick="closeModal('tenantModal')"></div>
    <div class="relative bg-white rounded-3xl shadow-premium w-full max-w-md mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="tenant">
            <?php csrf_field(); ?>
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex justify-between items-center">
                <h3 id="tnModalTitle" class="font-bold text-xl tracking-tight">Tambah Tenant</h3>
                <button type="button" onclick="closeModal('tenantModal')" class="text-on-surface-variant hover:bg-surface-dim rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-8 flex flex-col gap-5">
                <input type="hidden" name="action" id="tnFormAction" value="create_tenant">
                <input type="hidden" name="id" id="tnId">
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama Tenant / Brand</label><input type="text" name="name" id="tnName" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Kategori</label>
                    <select name="kategori_usaha_id" id="tnCat" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                        <?php foreach($kategori_usaha as $ku): ?>
                        <option value="<?= $ku['id'] ?>"><?= htmlspecialchars($ku['nama_usaha']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama PIC</label><input type="text" name="pic_name" id="tnPic" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Telepon PIC</label><input type="text" name="pic_phone" id="tnPhone" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
            </div>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-end gap-3">
                <button type="button" onclick="closeModal('tenantModal')" class="px-6 py-3 border border-outline text-on-surface rounded-xl font-bold text-sm hover:bg-surface-dim transition-all">Batal</button>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl shadow-soft font-bold text-sm hover:bg-primary-hover transition-all">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="boothModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-overlay opacity-0" onclick="closeModal('boothModal')"></div>
    <div class="relative bg-white rounded-3xl shadow-premium w-full max-w-md mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="booth">
            <?php csrf_field(); ?>
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex justify-between items-center">
                <h3 id="bModalTitle" class="font-bold text-xl tracking-tight">Tambah Booth</h3>
                <button type="button" onclick="closeModal('boothModal')" class="text-on-surface-variant hover:bg-surface-dim rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-8 flex flex-col gap-5">
                <input type="hidden" name="action" id="bFormAction" value="create_booth">
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Kode Booth (ID)</label><input type="text" name="id" id="bId" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold focus:bg-white transition-all" placeholder="Contoh: A01"></div>
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Lokasi</label>
                    <select name="lokasi_booth_id" id="bLoc" class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                        <?php foreach($lokasi_booth as $lb): ?>
                        <option value="<?= $lb['id'] ?>"><?= htmlspecialchars($lb['nama_lokasi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama Area/Booth</label><input type="text" name="name" id="bName" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Harga Sewa (Rp)</label><input type="number" name="rent_price" id="bPrice" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Pilih Tenant (Opsional)</label>
                    <select name="tenant_id" id="bTenant" class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                        <option value="">- Tersedia (Belum Disewa) -</option>
                        <?php foreach($tenants as $tn): ?>
                            <option value="<?= $tn['id'] ?>"><?= htmlspecialchars($tn['nama_tenant']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-end gap-3">
                <button type="button" onclick="closeModal('boothModal')" class="px-6 py-3 border border-outline text-on-surface rounded-xl font-bold text-sm hover:bg-surface-dim transition-all">Batal</button>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl shadow-soft font-bold text-sm hover:bg-primary-hover transition-all">Simpan Booth</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab) switchTab(tab);

    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.ajax-form')) {
            e.preventDefault();
            const form = e.target;
            const targetName = form.dataset.target;
            ajaxSubmit(form, {
                onSuccess: () => {
                    closeModal(targetName + 'Modal');
                    refreshPartial(`tbody-${targetName}`, targetName);
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
        document.getElementById('bId').readOnly = false;
        document.getElementById('bName').value = '';
        document.getElementById('bPrice').value = '';
        document.getElementById('bTenant').value = '';
    }
    openModal(id);
}

function editTenant(data) {
    document.getElementById('tnModalTitle').innerText = 'Edit Tenant';
    document.getElementById('tnFormAction').value = 'edit_tenant';
    document.getElementById('tnId').value = data.id;
    document.getElementById('tnName').value = data.nama_tenant;
    document.getElementById('tnCat').value = data.kategori_usaha_id;
    document.getElementById('tnPic').value = data.nama_pic;
    document.getElementById('tnPhone').value = data.telepon_pic;
    openModal('tenantModal');
}

function editBooth(data) {
    document.getElementById('bModalTitle').innerText = 'Edit Booth';
    document.getElementById('bFormAction').value = 'edit_booth';
    document.getElementById('bId').value = data.id;
    const idInput = document.getElementById('bId');
    if (idInput) idInput.readOnly = true;
    document.getElementById('bName').value = data.nama_area || data.nama_booth || '';
    document.getElementById('bLoc').value = data.lokasi_booth_id || '';
    document.getElementById('bPrice').value = data.harga_sewa || '';
    document.getElementById('bTenant').value = data.tenant_id || '';
    openModal('boothModal');
}
</script>
</body>
</html>
