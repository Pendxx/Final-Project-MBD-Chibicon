<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/services/PublicService.php';

$publicService = new PublicService($db);
$tickets = $publicService->getAvailableTickets();
$guestStars = $publicService->getGuestStars();
$rundown = $publicService->getPublicRundown();
$paymentMethods = $db->query("SELECT * FROM Metode_Pembayaran")->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Chibicon 2026 | The Ultimate Anime Convention</title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@400;500;600;700&family=Geist:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries,aspect-ratio"></script>
    <link href="assets/css/app.css" rel="stylesheet" />
    <script src="assets/js/app.js" defer></script>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                        display: ['Geist', 'sans-serif'],
                        mono: ['Geist Mono', 'monospace'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: 'var(--accent)',
                            hover: 'var(--accent-hover)',
                        },
                        surface: {
                            DEFAULT: 'var(--bg-surface)',
                            dim: 'var(--bg-surface-dim)',
                            bright: 'var(--bg-surface-bright)',
                        },
                        "on-surface": 'var(--text-main)',
                        "on-surface-variant": 'var(--text-muted)',
                        outline: {
                            DEFAULT: 'var(--glass-border)',
                            variant: 'var(--bg-surface-dim)',
                        },
                        glass: 'var(--glass-bg)',
                    },
                    boxShadow: {
                        'soft': 'var(--shadow-soft)',
                        'premium': 'var(--shadow-premium)',
                    },
                    transitionTimingFunction: {
                        'premium': 'var(--premium-bezier)',
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen antialiased transition-colors duration-500 overflow-x-hidden selection:bg-primary selection:text-white relative">

    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 px-6 py-6 transition-all duration-500">
        <div class="max-w-7xl mx-auto w-full flex items-center justify-between px-6 py-4 bg-glass backdrop-blur-2xl border border-outline shadow-soft rounded-[2rem]">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse" style="box-shadow: 0 0 12px var(--accent)"></span>
                <span class="font-display font-extrabold text-xl tracking-tight text-on-surface">CHIBICON</span>
            </div>
            
            <div class="hidden md:flex items-center gap-8 font-semibold text-sm text-on-surface-variant">
                <a href="#gueststars" class="hover:text-primary transition-colors">Guest Stars</a>
                <a href="#schedule" class="hover:text-primary transition-colors">Schedule</a>
                <a href="#tickets" class="hover:text-primary transition-colors">Tickets</a>
                <button onclick="openCheckTicket()" class="hover:text-primary transition-colors">My Tickets</button>
            </div>

            <div class="flex items-center gap-4">
                <button onclick="toggleDark()" class="w-10 h-10 rounded-full flex items-center justify-center bg-surface-dim text-on-surface hover:bg-primary hover:text-white transition-all border border-outline">
                    <span id="darkIcon" class="material-symbols-outlined text-[20px]">dark_mode</span>
                </button>
                <a href="#tickets" class="btn-premium hidden sm:flex items-center gap-2 bg-on-surface text-surface px-6 py-2.5 rounded-full font-bold text-sm hover:scale-105 transition-transform duration-300">
                    Get Tickets
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-48 pb-32 px-6 lg:min-h-[90vh] flex flex-col items-center justify-center text-center">
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none flex items-center justify-center">
            <div class="w-[800px] h-[800px] rounded-full bg-primary opacity-[0.05] blur-[120px]"></div>
            <div class="absolute w-[600px] h-[600px] rounded-full bg-purple-500 opacity-[0.03] blur-[100px] translate-x-1/2 translate-y-1/3"></div>
        </div>

        <div class="relative z-10 max-w-5xl mx-auto space-y-8 animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-outline bg-glass backdrop-blur-md">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                </span>
                <span class="text-xs font-bold uppercase tracking-widest text-primary">Tickets Now Live</span>
            </div>
            
            <h1 class="font-display font-black text-6xl md:text-8xl lg:text-[120px] leading-[0.9] tracking-tighter text-on-surface drop-shadow-sm">
                THE NEXUS OF<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-purple-600">ANIME & POP</span>
            </h1>
            
            <p class="text-lg md:text-xl text-on-surface-variant font-medium max-w-2xl mx-auto leading-relaxed">
                Step into the ultimate convention experience. Chibicon 2026 brings together the biggest names in anime, cosplay, and pop culture for one unforgettable weekend.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-8">
                <a href="#tickets" class="btn-premium w-full sm:w-auto bg-primary text-white px-10 py-4 rounded-full font-extrabold text-lg shadow-[0_0_40px_rgba(255,42,95,0.4)] hover:shadow-[0_0_60px_rgba(255,42,95,0.6)] transition-all">
                    Secure Your Pass
                </a>
                <a href="#gueststars" class="w-full sm:w-auto px-10 py-4 rounded-full font-bold text-lg text-on-surface border border-outline hover:bg-surface-dim transition-all">
                    Explore Lineup
                </a>
            </div>
        </div>
    </section>

    <!-- Guest Stars -->
    <section id="gueststars" class="py-24 px-6 relative z-10">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-20">
                <h2 class="font-display font-black text-4xl md:text-6xl tracking-tight text-on-surface mb-6">Star-Studded<br>Lineup</h2>
                <p class="text-on-surface-variant text-lg font-medium">Meet the legends of the industry.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if(empty($guestStars)): ?>
                    <div class="col-span-full text-center text-on-surface-variant py-12 font-medium">More guests to be announced soon!</div>
                <?php else: ?>
                    <?php foreach($guestStars as $index => $star): ?>
                        <div class="premium-card-outer animate-fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s">
                            <div class="premium-card-inner flex flex-col p-8 items-center text-center group">
                                <div class="w-32 h-32 rounded-full mb-6 border-4 border-surface-bright overflow-hidden shadow-premium group-hover:scale-110 group-hover:border-primary transition-all duration-500">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($star['nama_panggung']) ?>&background=random&size=256" alt="<?= htmlspecialchars($star['nama_panggung']) ?>" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-2xl font-bold font-display text-on-surface mb-2 group-hover:text-primary transition-colors"><?= htmlspecialchars($star['nama_panggung']) ?></h3>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest bg-primary/10 text-primary border border-primary/20 mt-2">
                                    <?= htmlspecialchars($star['nama_kategori'] ?? 'Special Guest') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Schedule (Rundown) -->
    <section id="schedule" class="py-24 px-6 relative z-10 bg-surface-dim/30 border-y border-outline">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-20">
                <h2 class="font-display font-black text-4xl md:text-6xl tracking-tight text-on-surface mb-6">Event<br>Schedule</h2>
                <p class="text-on-surface-variant text-lg font-medium">Plan your ultimate convention weekend.</p>
            </div>

            <div class="space-y-6">
                <?php if(empty($rundown)): ?>
                    <div class="text-center text-on-surface-variant py-12 font-medium">Schedule is being finalized. Stay tuned!</div>
                <?php else: ?>
                    <?php foreach($rundown as $index => $item): ?>
                        <div class="premium-card-outer animate-fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s">
                            <div class="premium-card-inner p-6 md:p-8 flex flex-col md:flex-row gap-6 items-start md:items-center">
                                <div class="flex-shrink-0 md:w-48 text-primary font-bold font-mono text-xl">
                                    <?= date('H:i', strtotime($item['tgl_wkt_mulai'])) ?> - <?= date('H:i', strtotime($item['tgl_wkt_akhir'])) ?>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-2xl font-bold font-display text-on-surface mb-2"><?= htmlspecialchars($item['nama_kegiatan']) ?></h3>
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-on-surface-variant font-medium">
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[18px]">location_on</span>
                                            <?= htmlspecialchars($item['nama_lokasi'] ?? 'Main Stage') ?>
                                        </div>
                                        <?php if($item['guest_stars']): ?>
                                            <div class="w-1.5 h-1.5 rounded-full bg-outline hidden sm:block"></div>
                                            <div class="flex items-center gap-1.5 text-primary">
                                                <span class="material-symbols-outlined text-[18px]">star</span>
                                                <?= htmlspecialchars($item['guest_stars']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Tickets -->
    <section id="tickets" class="py-24 px-6 relative z-10">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-20">
                <h2 class="font-display font-black text-4xl md:text-6xl tracking-tight text-on-surface mb-6">Secure Your<br>Access</h2>
                <p class="text-on-surface-variant text-lg font-medium">Limited tickets available. Don't miss out.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-stretch justify-center">
                <?php if(empty($tickets)): ?>
                    <div class="col-span-full text-center text-on-surface-variant py-12 font-medium">Tickets are currently unavailable.</div>
                <?php else: ?>
                    <?php foreach($tickets as $index => $ticket): ?>
                        <div class="premium-card-outer h-full animate-fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s">
                            <div class="premium-card-inner flex flex-col p-8 h-full relative overflow-hidden group">
                                
                                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <span class="material-symbols-outlined text-9xl">confirmation_number</span>
                                </div>

                                <div class="mb-8 relative z-10">
                                    <h3 class="text-3xl font-display font-black text-on-surface mb-3"><?= htmlspecialchars($ticket['nama_tiket']) ?></h3>
                                    <div class="flex items-baseline gap-1 text-primary">
                                        <span class="text-lg font-bold">Rp</span>
                                        <span class="text-5xl font-black tracking-tighter"><?= number_format($ticket['harga'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
                                
                                <div class="space-y-4 mb-10 flex-grow relative z-10">
                                    <div class="flex items-center gap-3 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[20px]">check_circle</span>
                                        <span class="font-medium">Full Day Access</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[20px]">check_circle</span>
                                        <span class="font-medium">Access to All Stages</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[20px]">check_circle</span>
                                        <span class="font-medium">Merchant Area Entry</span>
                                    </div>
                                    <?php if(strpos(strtolower($ticket['nama_tiket']), 'vip') !== false): ?>
                                    <div class="flex items-center gap-3 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[20px]">check_circle</span>
                                        <span class="font-medium">Priority Queue & Seating</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary text-[20px]">check_circle</span>
                                        <span class="font-medium">Exclusive Merchandise</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="pt-6 border-t border-outline flex flex-col gap-4 mt-auto relative z-10">
                                    <div class="flex justify-between items-center text-sm font-bold">
                                        <span class="text-on-surface-variant">Availability</span>
                                        <span class="text-primary bg-primary/10 px-3 py-1 rounded-full"><?= number_format($ticket['kuota']) ?> Left</span>
                                    </div>
                                    <button onclick='openCheckout(<?= json_encode($ticket) ?>)' class="btn-premium w-full bg-on-surface text-surface py-4 rounded-2xl font-bold text-lg hover:scale-[1.02] transition-transform shadow-premium">
                                        Buy Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-outline bg-surface text-center px-6 relative z-10">
        <p class="text-on-surface-variant font-bold text-sm">
            &copy; 2026 Chibicon. Organized by Nerdzao Elite. All rights reserved.
        </p>
    </footer>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md transition-opacity duration-500" onclick="closeCheckout()"></div>
        <div class="relative bg-surface border border-outline rounded-[2.5rem] w-full max-w-xl overflow-hidden shadow-premium animate-fade-in-up">
            <form action="purchase.php" method="POST" class="p-8 md:p-10 space-y-8">
                <?php csrf_field(); ?>
                <input type="hidden" name="ticket_id" id="formTicketId">
                <input type="hidden" name="amount_raw" id="formAmountRaw">
                
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-3xl font-display font-black tracking-tight" id="modalTicketName">Ticket Name</h3>
                        <p class="text-on-surface-variant font-medium mt-1">Complete your registration to secure your spot.</p>
                    </div>
                    <button type="button" onclick="closeCheckout()" class="w-10 h-10 rounded-full flex items-center justify-center bg-surface-dim hover:bg-primary/10 hover:text-primary transition-all">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-on-surface-variant ml-1">Full Name</label>
                        <input type="text" name="nama_lengkap" required placeholder="Enter your full name" class="w-full bg-surface-dim border-outline rounded-2xl px-4 py-3 focus:ring-primary focus:border-primary transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-on-surface-variant ml-1">ID Number (KTP/NIK)</label>
                        <input type="text" name="nomor_identitas" required placeholder="Enter your ID number" class="w-full bg-surface-dim border-outline rounded-2xl px-4 py-3 focus:ring-primary focus:border-primary transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-on-surface-variant ml-1">Email Address</label>
                        <input type="email" name="email" required placeholder="name@example.com" class="w-full bg-surface-dim border-outline rounded-2xl px-4 py-3 focus:ring-primary focus:border-primary transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-on-surface-variant ml-1">Phone Number</label>
                        <input type="tel" name="telepon" required placeholder="0812xxxx" class="w-full bg-surface-dim border-outline rounded-2xl px-4 py-3 focus:ring-primary focus:border-primary transition-all font-medium">
                    </div>
                </div>

                <div class="p-6 bg-surface-dim rounded-3xl space-y-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-bold">Quantity</label>
                            <div class="flex items-center bg-surface rounded-full border border-outline p-1">
                                <button type="button" onclick="updateQty(-1)" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-primary/10 hover:text-primary transition-all">-</button>
                                <input type="number" name="qty" id="formQty" value="1" min="1" readonly class="w-10 text-center bg-transparent border-none focus:ring-0 font-bold p-0">
                                <button type="button" onclick="updateQty(1)" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-primary/10 hover:text-primary transition-all">+</button>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold uppercase text-on-surface-variant opacity-60">Total Amount</p>
                            <p class="text-2xl font-display font-black text-primary" id="modalTotal">Rp 0</p>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-outline/50">
                        <label class="text-xs font-bold uppercase tracking-widest text-on-surface-variant ml-1">Payment Method</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach($paymentMethods as $pm): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="metode_pembayaran_id" value="<?= $pm['id'] ?>" <?= $pm['id'] == 1 ? 'checked' : '' ?> class="peer hidden">
                                    <div class="peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary border border-outline bg-surface rounded-xl px-4 py-2.5 text-xs font-bold text-center transition-all hover:bg-surface-bright">
                                        <?= htmlspecialchars($pm['nama_metode']) ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-premium w-full bg-primary text-white py-5 rounded-[1.5rem] font-black text-xl shadow-[0_0_30px_rgba(255,42,95,0.3)] hover:shadow-[0_0_50px_rgba(255,42,95,0.5)] transition-all">
                    Confirm Purchase
                </button>
            </form>
        </div>
    </div>

    <!-- My Tickets Modal -->
    <div id="checkTicketModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md transition-opacity duration-500" onclick="closeCheckTicket()"></div>
        <div class="relative bg-surface border border-outline rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-premium animate-fade-in-up">
            <div class="p-8 md:p-10 space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-3xl font-display font-black tracking-tight">My Tickets</h3>
                        <p class="text-on-surface-variant font-medium mt-1">Enter your ID Number to see your orders.</p>
                    </div>
                    <button type="button" onclick="closeCheckTicket()" class="w-10 h-10 rounded-full flex items-center justify-center bg-surface-dim hover:bg-primary/10 hover:text-primary transition-all">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="flex gap-4">
                    <input type="text" id="checkIdentity" placeholder="Enter your ID Number (KTP/NIK)" class="flex-1 bg-surface-dim border-outline rounded-2xl px-6 py-4 focus:ring-primary focus:border-primary transition-all font-bold">
                    <button onclick="fetchMyTickets()" class="bg-on-surface text-surface px-8 py-4 rounded-2xl font-black hover:scale-105 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined">search</span> Search
                    </button>
                </div>

                <div id="ticketResults" class="space-y-4 max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar">
                    <!-- Results will be injected here -->
                    <div class="text-center py-10 text-on-surface-variant opacity-40 font-medium">No results to show.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Status Toasts -->
    <?php if(isset($_GET['status'])): ?>
        <?php if($_GET['status'] === 'success' && isset($_SESSION['purchase_success'])): 
            $data = $_SESSION['purchase_success'];
            unset($_SESSION['purchase_success']);
        ?>
            <div class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[200] w-full max-w-md animate-fade-in-up">
                <div class="bg-green-500 text-white p-6 rounded-[2rem] shadow-2xl flex items-center gap-5">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-3xl">check_circle</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg leading-tight">Order Successful!</h4>
                        <p class="text-white/80 text-sm mt-1">Thank you, <?= htmlspecialchars($data['name']) ?>. Your transaction <b>#<?= $data['trx_id'] ?></b> is being processed.</p>
                    </div>
                </div>
            </div>
        <?php elseif($_GET['status'] === 'error' && isset($_SESSION['purchase_error'])): 
            $err = $_SESSION['purchase_error'];
            unset($_SESSION['purchase_error']);
        ?>
            <div class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[200] w-full max-w-md animate-fade-in-up">
                <div class="bg-red-500 text-white p-6 rounded-[2rem] shadow-2xl flex items-center gap-5">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-3xl">warning</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg leading-tight">Purchase Failed</h4>
                        <p class="text-white/80 text-sm mt-1"><?= htmlspecialchars($err) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        let currentTicket = null;

        function openCheckout(ticket) {
            currentTicket = ticket;
            document.getElementById('formTicketId').value = ticket.id;
            document.getElementById('modalTicketName').textContent = ticket.nama_tiket;
            document.getElementById('formQty').value = 1;
            updateDisplay();
            document.getElementById('checkoutModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function openCheckTicket() {
            document.getElementById('checkTicketModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeCheckTicket() {
            document.getElementById('checkTicketModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        async function fetchMyTickets() {
            const identity = document.getElementById('checkIdentity').value;
            if (!identity) return alert('Please enter your ID Number.');
            
            const resultsContainer = document.getElementById('ticketResults');
            resultsContainer.innerHTML = '<div class="text-center py-10"><span class="animate-spin material-symbols-outlined text-primary text-4xl">sync</span></div>';

            try {
                const response = await fetch('check_ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `nomor_identitas=${encodeURIComponent(identity)}`
                });
                const res = await response.json();
                
                if (res.success && res.data.length > 0) {
                    resultsContainer.innerHTML = res.data.map(t => `
                        <div class="p-6 bg-surface-dim border border-outline rounded-3xl group hover:border-primary transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-xs font-black text-primary uppercase tracking-widest">${t.trx_code}</p>
                                    <h4 class="text-lg font-bold mt-1">${t.items}</h4>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider ${t.status === 'Lunas' ? 'bg-green-500/20 text-green-500' : 'bg-yellow-500/20 text-yellow-500'}">
                                    ${t.status}
                                </span>
                            </div>
                            <div class="flex justify-between items-end border-t border-outline/50 pt-4">
                                <div class="text-xs text-on-surface-variant font-medium">
                                    ${new Date(t.time).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                    <br>Payment: ${t.payment_method}
                                </div>
                                <p class="text-xl font-display font-black text-on-surface">${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(t.amount)}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    resultsContainer.innerHTML = '<div class="text-center py-10 text-on-surface-variant font-medium">No tickets found for this ID.</div>';
                }
            } catch (err) {
                resultsContainer.innerHTML = '<div class="text-center py-10 text-red-500 font-medium">Failed to fetch data.</div>';
            }
        }

        function updateQty(delta) {
            const input = document.getElementById('formQty');
            let val = parseInt(input.value) + delta;
            if (val < 1) val = 1;
            if (val > currentTicket.kuota) val = currentTicket.kuota;
            input.value = val;
            updateDisplay();
        }

        function updateDisplay() {
            if (!currentTicket) return;
            const qty = parseInt(document.getElementById('formQty').value);
            const total = qty * currentTicket.harga;
            document.getElementById('modalTotal').textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(total);
            document.getElementById('formAmountRaw').value = total;
        }

        function toggleDark() {
            const isDark = document.documentElement.classList.toggle('dark');
            const icon = document.getElementById('darkIcon');
            icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            localStorage.setItem('darkMode', isDark ? '1' : '0');
        }

        // Auto hide toasts
        setTimeout(() => {
            document.querySelectorAll('[class*="fixed bottom-10"]').forEach(el => {
                el.classList.add('opacity-0', 'translate-y-10');
                setTimeout(() => el.remove(), 500);
            });
        }, 6000);
    </script>
</body>
</html>