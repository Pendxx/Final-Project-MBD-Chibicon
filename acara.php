<?php
// acara.php - Manajemen Acara (Guest Stars & Rundown)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Guest Star Actions
    if ($action === 'create_gs' || $action === 'edit_gs') {
        $id = $_POST['id'] ?? null;
        $stage_name = $_POST['stage_name'] ?? '';
        $agency = $_POST['agency'] ?? '';
        $country = $_POST['country'] ?? '';
        $kategori_talent_id = !empty($_POST['kategori_talent_id']) ? $_POST['kategori_talent_id'] : null;
        $manager = $_POST['manager_contact'] ?? '';
        
        if ($action === 'create_gs') {
            $stmt = $db->prepare("INSERT INTO Guest_Star (nama_panggung, agensi, negara, kategori_talent_id, kontak_manager) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$stage_name, $agency, $country, $kategori_talent_id, $manager]);
            $msg = "Guest Star berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Guest_Star SET nama_panggung=?, agensi=?, negara=?, kategori_talent_id=?, kontak_manager=? WHERE id=?");
            $stmt->execute([$stage_name, $agency, $country, $kategori_talent_id, $manager, $id]);
            $msg = "Guest Star berhasil diupdate!";
        }
    } elseif ($action === 'delete_gs') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $db->prepare("DELETE FROM Guest_Star WHERE id = ?")->execute([$id]);
            $msg = "Guest Star dihapus.";
        }
    }
    $redirect_tab = in_array($action, ['create_rundown','edit_rundown','delete_rundown']) ? 'rundown' : 'guest-star';
    
    // Rundown Actions
    if ($action === 'create_rundown' || $action === 'edit_rundown') {
        $id = $_POST['id'] ?? null;
        $tgl_wkt_mulai = str_replace('T', ' ', $_POST['start_time']) . ':00';
        $tgl_wkt_akhir = str_replace('T', ' ', $_POST['end_time']) . ':00';
        $activity = $_POST['activity_name'] ?? '';
        $lokasi_panggung_id = !empty($_POST['lokasi_panggung_id']) ? $_POST['lokasi_panggung_id'] : null;
        $gs_id = !empty($_POST['guest_star_id']) ? $_POST['guest_star_id'] : null;
        
        if ($action === 'create_rundown') {
            $stmt = $db->prepare("INSERT INTO Rundown (nama_kegiatan, tgl_wkt_mulai, tgl_wkt_akhir, lokasi_panggung_id, panitia_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$activity, $tgl_wkt_mulai, $tgl_wkt_akhir, $lokasi_panggung_id, 1]); // admin panitia
            $rd_id = $db->lastInsertId();
            
            if ($gs_id) {
                $db->prepare("INSERT INTO Guest_Star_Rundown (guest_star_id, rundown_id) VALUES (?, ?)")->execute([$gs_id, $rd_id]);
            }
            $msg = "Sesi Rundown berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Rundown SET nama_kegiatan=?, tgl_wkt_mulai=?, tgl_wkt_akhir=?, lokasi_panggung_id=? WHERE id=?");
            $stmt->execute([$activity, $tgl_wkt_mulai, $tgl_wkt_akhir, $lokasi_panggung_id, $id]);
            
            $db->prepare("DELETE FROM Guest_Star_Rundown WHERE rundown_id=?")->execute([$id]);
            if ($gs_id) {
                $db->prepare("INSERT INTO Guest_Star_Rundown (guest_star_id, rundown_id) VALUES (?, ?)")->execute([$gs_id, $id]);
            }
            $msg = "Sesi Rundown berhasil diupdate!";
        }
    } elseif ($action === 'delete_rundown') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $db->prepare("DELETE FROM Rundown WHERE id = ?")->execute([$id]);
            $msg = "Sesi Rundown dihapus.";
        }
    }
    
    // Check if AJAX Request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $redirect_tab = in_array($action, ['create_rundown','edit_rundown','delete_rundown']) ? 'rundown' : 'guest-star';
    $_SESSION['toast'] = $msg;
    header("Location: acara.php?tab=" . $redirect_tab);
    exit;
}

// Partial rendering for AJAX table refresh
if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'guest-star') {
        $guest_stars = $db->query("
            SELECT gs.*, kt.nama_kategori as category, CONCAT('GS-', LPAD(gs.id, 3, '0')) as gs_code 
            FROM Guest_Star gs 
            LEFT JOIN Kategori_Talent kt ON gs.kategori_talent_id = kt.id 
            ORDER BY gs.id ASC
        ")->fetchAll();
        foreach($guest_stars as $gs): ?>
        <tr class="hover:bg-surface-container-low transition-colors group">
            <td class="p-4 font-mono text-on-surface-variant"><?= $gs['gs_code'] ?></td>
            <td class="p-4 font-semibold text-on-surface"><?= htmlspecialchars($gs['nama_panggung']) ?></td>
            <td class="p-4 text-on-surface"><?= htmlspecialchars($gs['agensi']) ?: '<span class="italic text-on-surface-variant">Independent</span>' ?></td>
            <td class="p-4 flex items-center gap-2 text-on-surface"><?= htmlspecialchars($gs['negara']) ?></td>
            <td class="p-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-label-sm bg-primary-container text-on-primary-container">
                    <?= htmlspecialchars($gs['category']) ?>
                </span>
            </td>
            <td class="p-4 text-on-surface"><?= htmlspecialchars($gs['kontak_manager']) ?></td>
            <td class="p-4 text-center whitespace-nowrap">
                <button onclick='editGs(<?= json_encode($gs) ?>)' class="text-primary hover:text-primary-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete_gs">
                    <input type="hidden" name="id" value="<?= $gs['id'] ?>">
                    <button type="submit" class="text-error hover:text-error-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
    
    if ($_GET['partial'] === 'rundown') {
        $rundowns = $db->query("
            SELECT r.*, gs.nama_panggung as gs_name, lp.nama_lokasi as location, gs.id as guest_star_id 
            FROM Rundown r 
            LEFT JOIN Guest_Star_Rundown gsr ON r.id = gsr.rundown_id 
            LEFT JOIN Guest_Star gs ON gsr.guest_star_id = gs.id 
            LEFT JOIN Lokasi_Panggung lp ON r.lokasi_panggung_id = lp.id
            ORDER BY r.tgl_wkt_mulai ASC
        ")->fetchAll();
        foreach($rundowns as $r): ?>
        <tr class="hover:bg-surface-container-low transition-colors group">
            <td class="p-4">
                <div class="font-semibold text-on-surface"><?= date('d M Y', strtotime($r['tgl_wkt_mulai'])) ?></div>
                <div class="text-on-surface-variant"><?= date('H:i', strtotime($r['tgl_wkt_mulai'])) ?> - <?= date('H:i', strtotime($r['tgl_wkt_akhir'])) ?></div>
            </td>
            <td class="p-4">
                <div class="font-semibold text-on-surface"><?= htmlspecialchars($r['nama_kegiatan']) ?></div>
            </td>
            <td class="p-4 text-on-surface"><span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">location_on</span> <?= htmlspecialchars($r['location']) ?></span></td>
            <td class="p-4 font-semibold text-on-surface"><?= htmlspecialchars($r['gs_name'] ?? '-') ?></td>
            <td class="p-4 text-center whitespace-nowrap">
                <button onclick='editRundown(<?= json_encode($r) ?>)' class="text-primary hover:text-primary-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete_rundown">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="text-error hover:text-error-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
}

// Fetch Data
$guest_stars = $db->query("
    SELECT gs.*, kt.nama_kategori as category, CONCAT('GS-', LPAD(gs.id, 3, '0')) as gs_code 
    FROM Guest_Star gs 
    LEFT JOIN Kategori_Talent kt ON gs.kategori_talent_id = kt.id 
    ORDER BY gs.id ASC
")->fetchAll();

$rundowns = $db->query("
    SELECT r.*, gs.nama_panggung as gs_name, lp.nama_lokasi as location, gs.id as guest_star_id 
    FROM Rundown r 
    LEFT JOIN Guest_Star_Rundown gsr ON r.id = gsr.rundown_id 
    LEFT JOIN Guest_Star gs ON gsr.guest_star_id = gs.id 
    LEFT JOIN Lokasi_Panggung lp ON r.lokasi_panggung_id = lp.id
    ORDER BY r.tgl_wkt_mulai ASC
")->fetchAll();

$kategori_talent = $db->query("SELECT * FROM Kategori_Talent")->fetchAll();
$lokasi_panggung = $db->query("SELECT * FROM Lokasi_Panggung")->fetchAll();

$total_gs = count($guest_stars);
$total_rd = count($rundowns);

$page_title = "Manajemen Acara - Chibicon Admin";
$active_menu = "events";
include __DIR__ . '/components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h2 class="font-display-lg text-display-lg text-on-surface mb-1">Manajemen Acara</h2>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Kelola daftar Guest Star dan Rangkaian Rundown Chibicon 2024.</p>
        </div>
        <button class="bg-primary text-on-primary hover:bg-primary-container px-4 py-2 rounded-lg font-title-md text-title-md flex items-center gap-2 transition-colors shadow-sm whitespace-nowrap">
            <span class="material-symbols-outlined text-[20px]">file_download</span> Export
        </button>
    </div>

    <!-- Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-surface-container-lowest p-card-padding rounded-xl border border-outline-variant shadow-sm flex items-start justify-between">
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">Total Guest Stars</p>
                <p class="font-headline-lg text-headline-lg text-on-surface"><?= $total_gs ?></p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-secondary-container flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined text-[24px]">stars</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-card-padding rounded-xl border border-outline-variant shadow-sm flex items-start justify-between">
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">Total Sesi Rundown</p>
                <p class="font-headline-lg text-headline-lg text-on-surface"><?= $total_rd ?></p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px]">schedule</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-card-padding rounded-xl border border-outline-variant shadow-sm flex items-start justify-between">
            <div>
                <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">Panggung Aktif</p>
                <p class="font-headline-lg text-headline-lg text-on-surface">2</p>
            </div>
            <div class="w-12 h-12 rounded-lg bg-surface-container-high text-on-surface flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px]">stadium</span>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-outline-variant mb-6">
        <nav aria-label="Tabs" class="flex gap-6">
            <button class="tab-btn border-b-2 border-primary text-primary font-title-md py-3 px-1 transition-colors flex items-center gap-2" id="tab-guest-star" onclick="switchTab('guest-star')">
                <span class="material-symbols-outlined text-[18px]">person</span> Guest Star
            </button>
            <button class="tab-btn border-b-2 border-transparent text-on-surface-variant hover:text-on-surface font-title-md py-3 px-1 transition-colors flex items-center gap-2" id="tab-rundown" onclick="switchTab('rundown')">
                <span class="material-symbols-outlined text-[18px]">list_alt</span> Rundown
            </button>
        </nav>
    </div>

    <!-- Content Area -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden">
        
        <!-- GUEST STAR TAB -->
        <div class="tab-content" id="content-guest-star">
            <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <h3 class="font-title-lg text-on-surface">Daftar Guest Star</h3>
                <button onclick="openAcaraModal('gsModal')" class="bg-primary text-on-primary font-label-md px-4 py-2 rounded hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Guest Star
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-label-md uppercase">
                            <th class="p-4">ID</th>
                            <th class="p-4">Nama Panggung</th>
                            <th class="p-4">Agensi</th>
                            <th class="p-4">Negara</th>
                            <th class="p-4">Kategori</th>
                            <th class="p-4">Kontak</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-guest-star" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <?php foreach($guest_stars as $gs): ?>
                        <tr class="hover:bg-surface-container-low transition-colors group">
                            <td class="p-4 font-mono text-on-surface-variant"><?= $gs['gs_code'] ?></td>
                            <td class="p-4 font-semibold text-on-surface"><?= htmlspecialchars($gs['nama_panggung']) ?></td>
                            <td class="p-4 text-on-surface"><?= htmlspecialchars($gs['agensi']) ?: '<span class="italic text-on-surface-variant">Independent</span>' ?></td>
                            <td class="p-4 flex items-center gap-2 text-on-surface"><?= htmlspecialchars($gs['negara']) ?></td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-label-sm bg-primary-container text-on-primary-container">
                                    <?= htmlspecialchars($gs['category']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-on-surface"><?= htmlspecialchars($gs['kontak_manager']) ?></td>
                            <td class="p-4 text-center whitespace-nowrap">
                                <button onclick='editGs(<?= json_encode($gs) ?>)' class="text-primary hover:text-primary-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete_gs">
                                    <input type="hidden" name="id" value="<?= $gs['id'] ?>">
                                    <button type="submit" class="text-error hover:text-error-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RUNDOWN TAB -->
        <div class="tab-content hidden" id="content-rundown">
            <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <h3 class="font-title-lg text-on-surface">Jadwal Rundown</h3>
                <button onclick="openAcaraModal('rundownModal')" class="bg-primary text-on-primary font-label-md px-4 py-2 rounded hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Rundown
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-label-md uppercase">
                            <th class="p-4">Waktu</th>
                            <th class="p-4">Nama Kegiatan</th>
                            <th class="p-4">Lokasi</th>
                            <th class="p-4">Guest Star</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-rundown" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <?php foreach($rundowns as $r): ?>
                        <tr class="hover:bg-surface-container-low transition-colors group">
                            <td class="p-4">
                                <div class="font-semibold text-on-surface"><?= date('d M Y', strtotime($r['tgl_wkt_mulai'])) ?></div>
                                <div class="text-on-surface-variant"><?= date('H:i', strtotime($r['tgl_wkt_mulai'])) ?> - <?= date('H:i', strtotime($r['tgl_wkt_akhir'])) ?></div>
                            </td>
                            <td class="p-4">
                                <div class="font-semibold text-on-surface"><?= htmlspecialchars($r['nama_kegiatan']) ?></div>
                            </td>
                            <td class="p-4 text-on-surface"><span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-primary">location_on</span> <?= htmlspecialchars($r['location']) ?></span></td>
                            <td class="p-4 font-semibold text-on-surface"><?= htmlspecialchars($r['gs_name'] ?? '-') ?></td>
                            <td class="p-4 text-center whitespace-nowrap">
                                <button onclick='editRundown(<?= json_encode($r) ?>)' class="text-primary hover:text-primary-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete_rundown">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="text-error hover:text-error-container p-1 rounded"><span class="material-symbols-outlined text-[20px]">delete</span></button>
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

<!-- Modal: Guest Star -->
<div id="gsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('gsModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="guest-star">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="gsModalTitle" class="font-title-lg font-bold text-on-surface">Tambah Guest Star</h3>
                <button type="button" onclick="closeModal('gsModal')" class="text-on-surface-variant rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="gsFormAction" value="create_gs">
                <input type="hidden" name="id" id="gsId">
                <div><label class="block text-sm mb-1 text-on-surface">Nama Panggung</label><input type="text" name="stage_name" id="gsStage" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div><label class="block text-sm mb-1 text-on-surface">Agensi</label><input type="text" name="agency" id="gsAgency" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary" placeholder="Kosongkan jika Independent"></div>
                <div><label class="block text-sm mb-1 text-on-surface">Negara</label><input type="text" name="country" id="gsCountry" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div>
                    <label class="block text-sm mb-1 text-on-surface">Kategori</label>
                    <select name="kategori_talent_id" id="gsCategory" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                        <?php foreach($kategori_talent as $kt): ?>
                        <option value="<?= $kt['id'] ?>"><?= htmlspecialchars($kt['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-sm mb-1 text-on-surface">Kontak Manager</label><input type="text" name="manager_contact" id="gsManager" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('gsModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Rundown -->
<div id="rundownModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('rundownModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="rundown">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="rdModalTitle" class="font-title-lg font-bold text-on-surface">Tambah Rundown</h3>
                <button type="button" onclick="closeModal('rundownModal')" class="text-on-surface-variant rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="rdFormAction" value="create_rundown">
                <input type="hidden" name="id" id="rdId">
                <div class="flex gap-4">
                    <div class="flex-1"><label class="block text-sm mb-1 text-on-surface">Mulai</label><input type="datetime-local" name="start_time" id="rdStart" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                    <div class="flex-1"><label class="block text-sm mb-1 text-on-surface">Selesai</label><input type="datetime-local" name="end_time" id="rdEnd" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                </div>
                <div><label class="block text-sm mb-1 text-on-surface">Kegiatan</label><input type="text" name="activity_name" id="rdActivity" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary"></div>
                <div>
                    <label class="block text-sm mb-1 text-on-surface">Lokasi</label>
                    <select name="lokasi_panggung_id" id="rdLoc" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                        <?php foreach($lokasi_panggung as $lp): ?>
                        <option value="<?= $lp['id'] ?>"><?= htmlspecialchars($lp['nama_lokasi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1 text-on-surface">Guest Star</label>
                    <select name="guest_star_id" id="rdGs" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:outline-none focus:border-primary">
                        <option value="">- Tidak Ada -</option>
                        <?php foreach($guest_stars as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_panggung']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('rundownModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── AJAX submit bindings (bound early, before app.js defer overwrites globals)
document.addEventListener('DOMContentLoaded', function() {
    // Open create modal if ?action=create
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'create') openAcaraModal('gsModal');

    // ── AJAX form submit ───────────────────────────────────────────────────
    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.ajax-form')) {
            e.preventDefault();
            const form = e.target;
            const targetName = form.dataset.target; // guest-star or rundown
            ajaxSubmit(form, {
                onSuccess: () => {
                    const modalId = targetName === 'guest-star' ? 'gsModal' : 'rundownModal';
                    closeModal(modalId);
                    refreshPartial(`tbody-${targetName}`, targetName);
                    if (targetName === 'guest-star') {
                        refreshPartial('tbody-rundown', 'rundown');
                    }
                }
            });
        }
        if (e.target.matches('.delete-form')) {
            e.preventDefault();
            if (!confirm('Hapus item ini?')) return;
            const form = e.target;
            // content div IDs are now content-guest-star / content-rundown
            const tabContent = form.closest('.tab-content');
            const rawId = tabContent ? tabContent.id : '';
            const targetName = rawId.replace('content-', '');
            ajaxSubmit(form, {
                onSuccess: () => {
                    refreshPartial(`tbody-${targetName}`, targetName);
                    if (targetName === 'guest-star') {
                        refreshPartial('tbody-rundown', 'rundown');
                    }
                }
            });
        }
    });
});

// ── openAcaraModal: resets fields then shows — NOT overwritten by app.js ───
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
    }
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay')?.classList.remove('opacity-0');
    modal.querySelector('.modal-content')?.classList.remove('scale-95','opacity-0');
}

// ── editGs: populate & show gsModal ────────────────────────────────────────
function editGs(data) {
    document.getElementById('gsModalTitle').innerText = 'Edit Guest Star';
    document.getElementById('gsFormAction').value  = 'edit_gs';
    document.getElementById('gsId').value      = data.id;
    document.getElementById('gsStage').value   = data.nama_panggung;
    document.getElementById('gsAgency').value  = data.agensi;
    document.getElementById('gsCountry').value = data.negara;
    document.getElementById('gsCategory').value= data.kategori_talent_id;
    document.getElementById('gsManager').value = data.kontak_manager;
    const modal = document.getElementById('gsModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay')?.classList.remove('opacity-0');
    modal.querySelector('.modal-content')?.classList.remove('scale-95','opacity-0');
}

// ── editRundown: populate & show rundownModal ───────────────────────────────
function editRundown(data) {
    document.getElementById('rdModalTitle').innerText = 'Edit Rundown';
    document.getElementById('rdFormAction').value = 'edit_rundown';
    document.getElementById('rdId').value      = data.id;
    document.getElementById('rdStart').value   = data.tgl_wkt_mulai.replace(' ', 'T');
    document.getElementById('rdEnd').value     = data.tgl_wkt_akhir.replace(' ', 'T');
    document.getElementById('rdActivity').value= data.nama_kegiatan;
    document.getElementById('rdLoc').value     = data.lokasi_panggung_id;
    document.getElementById('rdGs').value      = data.guest_star_id || '';
    const modal = document.getElementById('rundownModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay')?.classList.remove('opacity-0');
    modal.querySelector('.modal-content')?.classList.remove('scale-95','opacity-0');
}
</script>
</body>
</html>
