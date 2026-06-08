<?php
// acara.php - Manajemen Guest Star & Rundown
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../services/AcaraService.php';

$acaraService = new AcaraService($db);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = $_POST['action'] ?? '';
    $msg = "Aksi tidak dikenal.";

    try {
        // Guest Star Actions
        if ($action === 'create_gs' || $action === 'edit_gs') {
            $id = $_POST['id'] ?? null;
            $stage_name = $_POST['stage_name'] ?? '';
            $agency = $_POST['agency'] ?? '';
            $country = $_POST['country'] ?? '';
            $category_id = $_POST['kategori_talent_id'] ?? null;
            $manager = $_POST['manager_contact'] ?? '';

            if ($action === 'create_gs') {
                $acaraService->createGuestStar($stage_name, $agency, $country, $category_id, $manager);
                $msg = "Guest Star baru berhasil ditambahkan!";
            } else {
                $acaraService->updateGuestStar($id, $stage_name, $agency, $country, $category_id, $manager);
                $msg = "Profil Guest Star berhasil diupdate!";
            }
        } elseif ($action === 'delete_gs') {
            $id = $_POST['id'] ?? null;
            if ($id && $acaraService->deleteGuestStar($id)) {
                $msg = "Guest Star berhasil dihapus.";
            }
        }

        // Rundown Actions
        if ($action === 'create_rundown' || $action === 'edit_rundown') {
            $id = $_POST['id'] ?? null;
            $activity = $_POST['activity_name'] ?? '';
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
            $loc_id = $_POST['lokasi_panggung_id'] ?? null;
            $panitia_id = $_POST['panitia_id'] ?? null;
            $gs_ids = $_POST['guest_star_ids'] ?? [];

            if ($action === 'create_rundown') {
                $acaraService->createRundown($activity, $start, $end, $loc_id, $gs_ids, $panitia_id);
                $msg = "Jadwal acara baru berhasil ditambahkan!";
            } else {
                $acaraService->updateRundown($id, $activity, $start, $end, $loc_id, $gs_ids, $panitia_id);
                $msg = "Rundown berhasil diperbarui!";
            }
        } elseif ($action === 'delete_rundown') {
            $id = $_POST['id'] ?? null;
            if ($id && $acaraService->deleteRundown($id)) {
                $msg = "Jadwal berhasil dihapus.";
            }
        }
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['toast'] = $msg;
    header("Location: acara.php?tab=" . (strpos($action, 'gs') !== false ? 'gs' : 'rundown'));
    exit;
}

// Partial rendering for AJAX
if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'guest-star') {
        $guest_stars = $acaraService->getAllGuestStars();
        foreach($guest_stars as $gs): ?>
        <tr class="group">
            <td class="font-extrabold text-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-black text-sm uppercase">
                        <?= substr($gs['nama_panggung'], 0, 1) ?>
                    </div>
                    <?= htmlspecialchars($gs['nama_panggung']) ?>
                </div>
            </td>
            <td class="font-bold text-on-surface-variant"><?= htmlspecialchars($gs['agensi']) ?></td>
            <td>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-dim text-on-surface text-[10px] font-extrabold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-[14px] text-primary">public</span>
                    <?= htmlspecialchars($gs['negara']) ?>
                </span>
            </td>
            <td>
                <span class="px-3 py-1 rounded-full bg-tertiary-container/30 text-on-tertiary-container text-[10px] font-extrabold uppercase tracking-wider">
                    <?= htmlspecialchars($gs['category_name']) ?>
                </span>
            </td>
            <td class="font-medium text-on-surface-variant"><?= htmlspecialchars($gs['kontak_manager']) ?></td>
            <td class="text-center">
                <div class="flex items-center justify-center gap-1">
                    <button onclick='editGs(<?= htmlspecialchars(json_encode($gs), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                    </button>
                    <form action="" method="POST" class="inline delete-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_gs">
                        <input type="hidden" name="id" value="<?= $gs['id'] ?>">
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
    
    if ($_GET['partial'] === 'rundown') {
        $rundowns = $acaraService->getAllRundowns();
        foreach($rundowns as $r): ?>
        <tr class="group">
            <td class="py-5">
                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-center justify-center w-14 h-14 rounded-2xl bg-surface-dim border border-outline-variant group-hover:bg-primary/10 transition-colors">
                        <span class="text-[10px] font-black text-primary uppercase"><?= date('M', strtotime($r['tgl_wkt_mulai'])) ?></span>
                        <span class="text-xl font-black text-[#111827]"><?= date('d', strtotime($r['tgl_wkt_mulai'])) ?></span>
                    </div>
                    <div>
                        <div class="font-black text-[#111827] tracking-tight"><?= date('H:i', strtotime($r['tgl_wkt_mulai'])) ?> — <?= date('H:i', strtotime($r['tgl_wkt_akhir'])) ?></div>
                        <div class="text-[10px] uppercase tracking-widest font-bold text-on-surface-variant opacity-60 mt-0.5">Waktu Pelaksanaan</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="font-extrabold text-lg text-[#111827] tracking-tight"><?= htmlspecialchars($r['nama_kegiatan']) ?></div>
            </td>
            <td>
                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-on-surface-variant">
                    <span class="material-symbols-outlined text-primary text-[18px]">location_on</span>
                    <?= htmlspecialchars($r['location']) ?>
                </span>
            </td>
            <td>
                <div class="flex flex-wrap gap-1">
                    <?php if($r['gs_name']): ?>
                        <span class="px-2 py-0.5 rounded-lg bg-primary/10 text-primary text-[10px] font-black uppercase"><?= htmlspecialchars($r['gs_name']) ?></span>
                    <?php else: ?>
                        <span class="text-on-surface-variant opacity-40 text-xs font-bold">— No GS</span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="text-center">
                <div class="flex items-center justify-center gap-1">
                    <button onclick='editRundown(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                    <form action="" method="POST" class="inline delete-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_rundown">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="w-9 h-9 rounded-xl hover:bg-error/10 text-error transition-all flex items-center justify-center active:scale-90 border border-error/20">
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

// Data Fetching
$guest_stars = $acaraService->getAllGuestStars();
$rundowns = $acaraService->getAllRundowns();
$kategori_talent = $acaraService->getKategoriTalent();
$lokasi_panggung = $acaraService->getLokasiPanggung();
$panitia_list = $db->query("SELECT id, nama_lengkap FROM Panitia")->fetchAll();

$page_title = "Events & Guest Stars";
$active_menu = "events";
include __DIR__ . '/../components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto bg-surface">
    <!-- Page Header -->
    <div class="mb-10 animate-fade-in-up">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="rounded-full px-3 py-1 bg-primary/10 text-primary text-[10px] uppercase tracking-[0.2em] font-bold">Programming & Talent</span>
                </div>
                <h2 class="font-extrabold text-4xl tracking-tight text-[#111827]">Event &amp; Guest Stars</h2>
                <p class="text-on-surface-variant mt-2 font-medium">Atur jadwal panggung dan kelola pengisi acara Chibicon.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="openAcaraModal('gsModal')" class="bg-[#111827] text-white font-bold text-sm px-6 py-3 rounded-2xl hover:bg-black transition-all shadow-soft flex items-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">star</span>
                    Tambah Talent
                </button>
                <button onclick="openAcaraModal('rundownModal')" class="bg-primary text-white font-bold text-sm px-6 py-3 rounded-2xl hover:bg-primary-hover transition-all shadow-soft flex items-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">schedule</span>
                    Tambah Jadwal
                </button>
            </div>
        </div>
    </div>

    <!-- Modern Tab Navigation -->
    <div class="flex p-1.5 bg-surface-dim rounded-2xl w-max border border-outline-variant mb-10 animate-fade-in-up stagger-1">
        <button onclick="switchTab('rundown')" id="tab-rundown" class="tab-btn active px-8 py-2.5 rounded-xl font-bold text-sm transition-all duration-500 ease-premium">
            Rundown Acara
        </button>
        <button onclick="switchTab('gs')" id="tab-gs" class="tab-btn px-8 py-2.5 rounded-xl font-bold text-sm text-on-surface-variant hover:text-primary transition-all duration-500 ease-premium">
            Guest Stars
        </button>
    </div>

    <!-- TAB: RUNDOWN -->
    <div class="tab-content block" id="content-rundown">
        <div class="premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Waktu & Tanggal</th>
                                <th>Nama Kegiatan</th>
                                <th>Panggung</th>
                                <th>Talent</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-rundown">
                            <?php foreach($rundowns as $r): ?>
                            <tr class="group">
                                <td class="py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex flex-col items-center justify-center w-14 h-14 rounded-2xl bg-surface-dim border border-outline-variant group-hover:bg-primary/10 transition-colors">
                                            <span class="text-[10px] font-black text-primary uppercase"><?= date('M', strtotime($r['tgl_wkt_mulai'])) ?></span>
                                            <span class="text-xl font-black text-[#111827]"><?= date('d', strtotime($r['tgl_wkt_mulai'])) ?></span>
                                        </div>
                                        <div>
                                            <div class="font-black text-[#111827] tracking-tight"><?= date('H:i', strtotime($r['tgl_wkt_mulai'])) ?> — <?= date('H:i', strtotime($r['tgl_wkt_akhir'])) ?></div>
                                            <div class="text-[10px] uppercase tracking-widest font-bold text-on-surface-variant opacity-60 mt-0.5">Waktu Pelaksanaan</div>
                                        </div>
                                    </div>
                                </td>
                                <td><div class="font-extrabold text-lg text-[#111827] tracking-tight"><?= htmlspecialchars($r['nama_kegiatan']) ?></div></td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-sm font-bold text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[18px]">location_on</span>
                                        <?= htmlspecialchars($r['location']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <?php if($r['gs_name']): ?>
                                            <span class="px-2 py-0.5 rounded-lg bg-primary/10 text-primary text-[10px] font-black uppercase"><?= htmlspecialchars($r['gs_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant opacity-40 text-xs font-bold">— No GS</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='editRundown(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)' class="w-8 h-8 rounded-lg hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90 border border-outline">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <form action="" method="POST" class="inline delete-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_rundown">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
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

    <!-- TAB: GUEST STARS -->
    <div class="tab-content hidden" id="content-gs">
        <div class="premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Nama Panggung</th>
                                <th>Agensi</th>
                                <th>Asal Negara</th>
                                <th>Kategori</th>
                                <th>Kontak Manajer</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-guest-star">
                            <?php foreach($guest_stars as $gs): ?>
                            <tr class="group">
                                <td class="font-extrabold text-[#111827]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-black text-sm uppercase">
                                            <?= substr($gs['nama_panggung'], 0, 1) ?>
                                        </div>
                                        <?= htmlspecialchars($gs['nama_panggung']) ?>
                                    </div>
                                </td>
                                <td class="font-bold text-on-surface-variant"><?= htmlspecialchars($gs['agensi']) ?></td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-dim text-on-surface text-[10px] font-extrabold uppercase tracking-wider">
                                        <span class="material-symbols-outlined text-[14px] text-primary">public</span>
                                        <?= htmlspecialchars($gs['negara']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="px-3 py-1 rounded-full bg-tertiary-container/30 text-on-tertiary-container text-[10px] font-extrabold uppercase tracking-wider">
                                        <?= htmlspecialchars($gs['category_name']) ?>
                                    </span>
                                </td>
                                <td class="font-medium text-on-surface-variant"><?= htmlspecialchars($gs['kontak_manager']) ?></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='editGs(<?= htmlspecialchars(json_encode($gs), ENT_QUOTES) ?>)' class="w-9 h-9 rounded-xl hover:bg-primary/10 text-primary transition-all flex items-center justify-center active:scale-90">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </button>
                                        <form action="" method="POST" class="inline delete-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_gs">
                                            <input type="hidden" name="id" value="<?= $gs['id'] ?>">
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
</main>

<!-- Modals -->
<div id="gsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-overlay opacity-0" onclick="closeModal('gsModal')"></div>
    <div class="relative bg-white rounded-3xl shadow-premium w-full max-w-md mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="guest-star">
            <?php csrf_field(); ?>
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex justify-between items-center">
                <h3 id="gsModalTitle" class="font-bold text-xl tracking-tight">Profil Guest Star</h3>
                <button type="button" onclick="closeModal('gsModal')" class="text-on-surface-variant hover:bg-surface-dim rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-8 flex flex-col gap-5">
                <input type="hidden" name="action" id="gsFormAction" value="create_gs">
                <input type="hidden" name="id" id="gsId">
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama Panggung</label><input type="text" name="stage_name" id="gsStage" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Agensi</label><input type="text" name="agency" id="gsAgency" class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                    <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Asal Negara</label><input type="text" name="country" id="gsCountry" class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                </div>
                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Kategori Talent</label>
                    <select name="kategori_talent_id" id="gsCategory" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                        <?php foreach($kategori_talent as $kt): ?>
                        <option value="<?= $kt['id'] ?>"><?= htmlspecialchars($kt['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Kontak Manajer</label><input type="text" name="manager_contact" id="gsManager" class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
            </div>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-end gap-3">
                <button type="button" onclick="closeModal('gsModal')" class="px-6 py-3 border border-outline text-on-surface rounded-xl font-bold text-sm hover:bg-surface-dim transition-all">Batal</button>
                <button type="submit" class="px-6 py-3 bg-[#111827] text-white rounded-xl shadow-soft font-bold text-sm hover:bg-black transition-all">Simpan Talent</button>
            </div>
        </form>
    </div>
</div>

<div id="rundownModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm modal-overlay opacity-0" onclick="closeModal('rundownModal')"></div>
    <div class="relative bg-white rounded-3xl shadow-premium w-full max-w-lg mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="rundown">
            <?php csrf_field(); ?>
            <div class="px-8 py-6 border-b border-outline-variant bg-surface-bright flex justify-between items-center">
                <h3 id="rdModalTitle" class="font-bold text-xl tracking-tight">Detail Rundown</h3>
                <button type="button" onclick="closeModal('rundownModal')" class="text-on-surface-variant hover:bg-surface-dim rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-8 flex flex-col gap-5 max-h-[70vh] overflow-y-auto">
                <input type="hidden" name="action" id="rdFormAction" value="create_rundown">
                <input type="hidden" name="id" id="rdId">
                
                <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Nama Kegiatan</label><input type="text" name="activity_name" id="rdActivity" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-black tracking-tight text-lg focus:bg-white transition-all"></div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Waktu Mulai</label><input type="datetime-local" name="start_time" id="rdStart" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                    <div><label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Waktu Selesai</label><input type="datetime-local" name="end_time" id="rdEnd" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-medium focus:bg-white transition-all"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Panggung</label>
                        <select name="lokasi_panggung_id" id="rdLoc" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                            <?php foreach($lokasi_panggung as $lp): ?>
                            <option value="<?= $lp['id'] ?>"><?= htmlspecialchars($lp['nama_lokasi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Penanggung Jawab</label>
                        <select name="panitia_id" id="rdPanitia" required class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all">
                            <?php foreach($panitia_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-on-surface-variant mb-2">Pilih Guest Star</label>
                    <select name="guest_star_ids[]" id="rdGs" multiple class="w-full px-4 py-3 rounded-xl bg-surface-dim border-transparent font-bold text-on-surface-variant focus:bg-white transition-all h-32">
                        <option value="">- Tanpa Guest Star -</option>
                        <?php foreach($guest_stars as $gs): ?>
                        <option value="<?= $gs['id'] ?>"><?= htmlspecialchars($gs['nama_panggung']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-8 py-6 border-t border-outline-variant bg-surface-bright flex justify-end gap-3">
                <button type="button" onclick="closeModal('rundownModal')" class="px-6 py-3 border border-outline text-on-surface rounded-xl font-bold text-sm hover:bg-surface-dim transition-all">Batal</button>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-xl shadow-soft font-bold text-sm hover:bg-primary-hover transition-all">Simpan Jadwal</button>
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
                    closeModal(form.closest('.modal-content').parentElement.id);
                    refreshPartial(`tbody-${targetName}`, targetName);
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
                }
            });
        }
    });
});

function openAcaraModal(id) {
    if (id === 'gsModal') {
        document.getElementById('gsModalTitle').innerText = 'Tambah Guest Star';
        document.getElementById('gsFormAction').value  = 'create_gs';
        document.getElementById('gsId').value      = '';
        document.getElementById('gsStage').value   = '';
        document.getElementById('gsAgency').value  = '';
        document.getElementById('gsCountry').value = '';
        document.getElementById('gsCategory').value= '';
        document.getElementById('gsManager').value = '';
    } else {
        document.getElementById('rdModalTitle').innerText = 'Tambah Rundown';
        document.getElementById('rdFormAction').value = 'create_rundown';
        document.getElementById('rdId').value      = '';
        document.getElementById('rdActivity').value= '';
        document.getElementById('rdPanitia').value = '';
    }
    openModal(id);
}

function editGs(data) {
    document.getElementById('gsModalTitle').innerText = 'Edit Guest Star';
    document.getElementById('gsFormAction').value  = 'edit_gs';
    document.getElementById('gsId').value      = data.id;
    document.getElementById('gsStage').value   = data.nama_panggung;
    document.getElementById('gsAgency').value  = data.agensi;
    document.getElementById('gsCountry').value = data.negara;
    document.getElementById('gsCategory').value= data.kategori_talent_id;
    document.getElementById('gsManager').value = data.kontak_manager;
    openModal('gsModal');
}

function editRundown(data) {
    document.getElementById('rdModalTitle').innerText = 'Edit Rundown';
    document.getElementById('rdFormAction').value = 'edit_rundown';
    document.getElementById('rdId').value      = data.id;
    if (data.tgl_wkt_mulai) {
        document.getElementById('rdStart').value = data.tgl_wkt_mulai.replace(' ', 'T').substring(0, 16);
    }
    if (data.tgl_wkt_akhir) {
        document.getElementById('rdEnd').value = data.tgl_wkt_akhir.replace(' ', 'T').substring(0, 16);
    }
    document.getElementById('rdActivity').value= data.nama_kegiatan || '';
    document.getElementById('rdLoc').value     = data.lokasi_panggung_id || '';
    document.getElementById('rdPanitia').value = data.panitia_id || '';
    document.getElementById('rdGs').value      = data.guest_star_id || '';
    openModal('rundownModal');
}
</script>
</body>
</html>
