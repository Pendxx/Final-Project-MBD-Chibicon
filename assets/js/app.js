/**
 * app.js — Chibicon Admin Shared Utilities
 * Dark mode, toast, notifications, quick actions, modal helpers, AJAX helpers
 */

/* ── Dark Mode ─────────────────────────────────────────────────────────────── */
(function initDarkMode() {
    if (localStorage.getItem('darkMode') === '1') {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
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

document.addEventListener('DOMContentLoaded', function () {
    _updateDarkUI(document.documentElement.classList.contains('dark'));
});

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
        opacity:0; transition:opacity 0.25s ease;
    `;
    el.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">${c.icon}</span>${message}`;
    document.body.appendChild(el);
    requestAnimationFrame(() => { el.style.opacity = '1'; });
    setTimeout(() => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, 4000);
}

/* ── Generic Modal ─────────────────────────────────────────────────────────── */
function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('hidden');
    void m.offsetWidth;
    m.querySelector('.modal-overlay')?.classList.remove('opacity-0');
    m.querySelector('.modal-content')?.classList.remove('scale-95', 'opacity-0');
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.querySelector('.modal-overlay')?.classList.add('opacity-0');
    m.querySelector('.modal-content')?.classList.add('scale-95', 'opacity-0');
    setTimeout(() => m.classList.add('hidden'), 300);
}

/* ── Notifications ─────────────────────────────────────────────────────────── */
function toggleNotif() {
    const panel = document.getElementById('notifPanel');
    if (!panel) return;
    panel.classList.toggle('hidden');
    // Hide dot when opening (but don't persist, it's just "seen")
    if (!panel.classList.contains('hidden')) {
        document.getElementById('notifDot')?.style.setProperty('display','none');
    }
}

function clearNotif() {
    document.getElementById('notifList')?.classList.add('hidden');
    document.getElementById('notifEmpty')?.classList.remove('hidden');
    document.getElementById('notifDot')?.style.setProperty('display','none');
    // Persist across page navigations
    localStorage.setItem('notifCleared', '1');
}

// Restore cleared state on page load
document.addEventListener('DOMContentLoaded', function () {
    if (localStorage.getItem('notifCleared') === '1') {
        document.getElementById('notifList')?.classList.add('hidden');
        document.getElementById('notifEmpty')?.classList.remove('hidden');
        document.getElementById('notifDot')?.style.setProperty('display','none');
    }
});

document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('notifWrapper');
    const panel   = document.getElementById('notifPanel');
    if (wrapper && panel && !wrapper.contains(e.target)) {
        panel.classList.add('hidden');
    }
});

/* ── Quick Actions modal ───────────────────────────────────────────────────── */
function openQuickAction()  { openModal('quickActionModal'); }
function closeQuickAction() { closeModal('quickActionModal'); }

/* ── AJAX form submitter ───────────────────────────────────────────────────── */
/**
 * Submit any <form> via AJAX.
 * @param {HTMLFormElement} form
 * @param {{ onSuccess?: (data:any)=>void, onError?: (data:any)=>void }} opts
 */
function ajaxSubmit(form, opts = {}) {
    const btn = form.querySelector('button[type=submit]');
    if (btn) btn.classList.add('btn-loading');

    fetch(window.location.pathname, {
        method : 'POST',
        body   : new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(data => {
        if (btn) btn.classList.remove('btn-loading');
        if (data.success) {
            showToast(data.message || 'Berhasil!', 'success');
            if (opts.onSuccess) opts.onSuccess(data);
        } else {
            showToast(data.message || 'Terjadi kesalahan.', 'error');
            if (opts.onError) opts.onError(data);
        }
    })
    .catch(err => {
        if (btn) btn.classList.remove('btn-loading');
        showToast('Koneksi gagal. Coba lagi.', 'error');
        console.error('[ajaxSubmit]', err);
    });
}

/**
 * Refresh a <tbody> by fetching ?partial=name from the same URL.
 * @param {string} tbodyId  – id of the <tbody> element
 * @param {string} name     – value sent as ?partial=<name>
 */
function refreshPartial(tbodyId, name) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    fetch(`${window.location.pathname}?partial=${name}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(r => r.text())
    .then(html => { tbody.innerHTML = html; })
    .catch(err => console.error('[refreshPartial]', err));
}

/* ── Tab switcher (shared across pages that use tabs) ──────────────────────── */
function switchTab(tabId, prefix) {
    prefix = prefix || 'tab';
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-primary','border-primary');
        btn.classList.add('text-on-surface-variant','border-transparent');
    });
    const activeBtn = document.getElementById(`${prefix}-${tabId}`);
    if (!activeBtn) return;
    activeBtn.classList.remove('text-on-surface-variant','border-transparent');
    activeBtn.classList.add('text-primary','border-primary');
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById(`content-${tabId}`)?.classList.remove('hidden');
}

/* Restore tab from URL ?tab= param */
document.addEventListener('DOMContentLoaded', function () {
    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab) switchTab(tab);
});
