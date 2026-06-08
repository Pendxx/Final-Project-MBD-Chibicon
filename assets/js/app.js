/**
 * app.js — Chibicon Admin Shared Utilities
 * Unified Modal, AJAX, and UI helpers
 */

/* ── Dark Mode ─────────────────────────────────────────────────────────────── */
(function initDarkMode() {
    const isDark = localStorage.getItem('darkMode');
    if (isDark === '1') {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
    } else if (isDark === '0') {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    }
})();

function toggleDark() {
    const isDark = document.documentElement.classList.toggle('dark');
    document.documentElement.classList.toggle('light', !isDark);
    localStorage.setItem('darkMode', isDark ? '1' : '0');
    _updateDarkUI(isDark);
}

function _updateDarkUI(isDark) {
    const icon  = document.getElementById('darkIcon');
    const label = document.getElementById('darkLabel');
    if (icon)  icon.textContent  = isDark ? 'light_mode' : 'dark_mode';
    if (label) label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
}

/* ── Toast ─────────────────────────────────────────────────────────────────── */
function showToast(message, type = 'success') {
    const colorMap = {
        success: { bg: '#03543F', text: '#ffffff', icon: 'check_circle' },
        error:   { bg: '#9B1C1C', text: '#ffffff', icon: 'error'        },
        warning: { bg: '#723B13', text: '#ffffff', icon: 'warning'      },
        info:    { bg: '#1E429F', text: '#ffffff', icon: 'info'         },
    };
    const c = colorMap[type] || colorMap.success;

    const el = document.createElement('div');
    el.style.cssText = `
        position:fixed; top:24px; right:24px; z-index:99999;
        background:${c.bg}; color:${c.text};
        padding:12px 20px; border-radius:10px;
        box-shadow:0 8px 24px rgba(0,0,0,0.25);
        display:flex; align-items:center; gap:10px;
        font-size:14px; font-weight:600;
        opacity:0; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateY(-20px);
    `;
    el.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">${c.icon}</span>${message}`;
    document.body.appendChild(el);
    
    // Animate in
    setTimeout(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    }, 10);

    // Auto remove
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-20px)';
        setTimeout(() => el.remove(), 300);
    }, 4000);
}

function toggleSidebar() {
    const sidebar = document.querySelector('aside.fixed');
    if (sidebar) {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('flex');
        // Add a back-overlay if opening
        if (!sidebar.classList.contains('hidden')) {
            const overlay = document.createElement('div');
            overlay.id = 'sidebar-overlay';
            overlay.className = 'fixed inset-0 bg-black/50 z-[45] md:hidden';
            overlay.onclick = toggleSidebar;
            document.body.appendChild(overlay);
        } else {
            document.getElementById('sidebar-overlay')?.remove();
        }
    }
}

/* ── Unified Modal System ──────────────────────────────────────────────────── */
function openModal(id) {
    const m = document.getElementById(id);
    if (!m) {
        console.error(`[openModal] Modal with ID "${id}" not found.`);
        return;
    }
    m.classList.remove('hidden');
    // Force reflow for animation
    void m.offsetWidth;
    const overlay = m.querySelector('.modal-overlay');
    const content = m.querySelector('.modal-content');
    
    if (overlay) overlay.classList.remove('opacity-0');
    if (content) content.classList.remove('scale-95', 'opacity-0');
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    
    const overlay = m.querySelector('.modal-overlay');
    const content = m.querySelector('.modal-content');
    
    if (overlay) overlay.classList.add('opacity-0');
    if (content) content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        m.classList.add('hidden');
    }, 300);
}

// Global modal close on ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="Modal"]:not(.hidden)').forEach(m => closeModal(m.id));
    }
});

/* ── Quick Actions ─────────────────────────────────────────────────────────── */
function openQuickAction()  { openModal('quickActionModal'); }
function closeQuickAction() { closeModal('quickActionModal'); }

/* ── AJAX helpers ──────────────────────────────────────────────────────────── */
/**
 * Submit any <form> via AJAX.
 */
async function ajaxSubmit(form, opts = {}) {
    const btn = form.querySelector('button[type=submit]');
    const originalText = btn ? btn.innerHTML : '';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="flex items-center gap-2 justify-center"><svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...</span>`;
    }

    try {
        const response = await fetch(window.location.href, { // Use href to include query params
            method : 'POST',
            body   : new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('[ajaxSubmit] Non-JSON response:', text);
            throw new Error('Server returned invalid format. Check logs.');
        }

        if (data.success) {
            showToast(data.message || 'Berhasil!', 'success');
            if (opts.onSuccess) opts.onSuccess(data);
        } else {
            showToast(data.message || 'Terjadi kesalahan.', 'error');
            if (opts.onError) opts.onError(data);
        }
    } catch (err) {
        showToast(err.message || 'Koneksi gagal.', 'error');
        console.error('[ajaxSubmit]', err);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }
}

/**
 * Refresh a component by fetching ?partial=name
 */
async function refreshPartial(containerId, name) {
    const el = document.getElementById(containerId);
    if (!el) return;
    
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('partial', name);
        
        const response = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        
        if (!response.ok) throw new Error('Refresh failed');
        const html = await response.text();
        el.innerHTML = html;
        
        // Dispatch custom event so pages can re-run init logic if needed
        el.dispatchEvent(new CustomEvent('partialRefreshed', { detail: { name } }));
    } catch (err) {
        console.error('[refreshPartial]', err);
    }
}

/* ── Tab Switcher (Fluid Motion) ──────────────────────────────────────────── */
function switchTab(tabId, prefix = 'tab', contentPrefix = 'content') {
    // Update Buttons
    document.querySelectorAll(`.${prefix}-btn`).forEach(btn => {
        btn.classList.remove('bg-white/10', 'text-white', 'border-white/5', 'shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)]', 'active');
        btn.classList.add('text-on-surface-variant', 'border-transparent');
    });
    
    const activeBtn = document.getElementById(`${prefix}-${tabId}`);
    if (activeBtn) {
        activeBtn.classList.remove('text-on-surface-variant', 'border-transparent');
        activeBtn.classList.add('bg-white/10', 'text-white', 'border-white/5', 'shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)]', 'active');
    }
    
    // Smooth Content Transition
    const activeContent = document.getElementById(`${contentPrefix}-${tabId}`);
    const currentlyVisible = document.querySelector(`.${prefix}-content:not(.hidden)`);

    if (currentlyVisible && currentlyVisible !== activeContent) {
        currentlyVisible.classList.add('fading-out');
        setTimeout(() => {
            currentlyVisible.classList.add('hidden');
            currentlyVisible.classList.remove('fading-out');
            
            if (activeContent) {
                activeContent.classList.remove('hidden');
                activeContent.classList.add('fading-in');
                setTimeout(() => activeContent.classList.remove('fading-in'), 300);
            }
        }, 300); // Wait for fade-out to finish
    } else if (activeContent && activeContent.classList.contains('hidden')) {
        activeContent.classList.remove('hidden');
        activeContent.classList.add('fading-in');
        setTimeout(() => activeContent.classList.remove('fading-in'), 300);
    }
}

/* ── Magnetic Hover Effect ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-premium').forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            btn.style.transform = `translate(${x * 0.15}px, ${y * 0.15}px) scale(1.05)`;
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = 'translate(0px, 0px) scale(1)';
        });
    });
});

/* ── Initializations ───────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    _updateDarkUI(document.documentElement.classList.contains('dark'));

    // Auto-tab from URL
    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab) switchTab(tab);
    
    // Notifications logic
    if (localStorage.getItem('notifCleared') === '1') {
        const list = document.getElementById('notifList');
        const empty = document.getElementById('notifEmpty');
        const dot = document.getElementById('notifDot');
        if (list) list.classList.add('hidden');
        if (empty) empty.classList.remove('hidden');
        if (dot) dot.style.display = 'none';
    }
});

// Generic event delegation for notif panel
document.addEventListener('click', (e) => {
    const wrapper = document.getElementById('notifWrapper');
    const panel   = document.getElementById('notifPanel');
    if (wrapper && panel && !wrapper.contains(e.target)) {
        panel.classList.add('hidden');
    }
});

function toggleNotif() {
    const panel = document.getElementById('notifPanel');
    if (panel) panel.classList.toggle('hidden');
}

function clearNotif() {
    localStorage.setItem('notifCleared', '1');
    const list = document.getElementById('notifList');
    const empty = document.getElementById('notifEmpty');
    const dot = document.getElementById('notifDot');
    if (list) list.classList.add('hidden');
    if (empty) empty.classList.remove('hidden');
    if (dot) dot.style.display = 'none';
}
