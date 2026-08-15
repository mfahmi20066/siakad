// sistem alert modern (modal konfirmasi + toast) tanpa dependensi. api: siToast, siConfirm, siHapus, siLogout
(function (window, document) {
    'use strict';

    var LOGOUT_URL = '/siakad/auth/logout.php';

    // CSRF token (dibaca dari <meta name="csrf-token">)
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Tambahkan parameter csrf_token ke URL (tidak menggandakan bila sudah ada)
    function appendCsrfToken(url) {
        var token = getCsrfToken();
        if (!token || !url) return url;
        var sep = url.indexOf('?') === -1 ? '?' : '&';
        return url + sep + 'csrf_token=' + encodeURIComponent(token);
    }

    var ICONS = {
        success:  { cls: 'sia-icon-success',  icon: 'fa-check-circle' },
        error:    { cls: 'sia-icon-error',    icon: 'fa-exclamation-circle' },
        delete:   { cls: 'sia-icon-danger',   icon: 'fa-trash' },
        warning:  { cls: 'sia-icon-warning',  icon: 'fa-exclamation-triangle' },
        info:     { cls: 'sia-icon-info',     icon: 'fa-info-circle' },
        question: { cls: 'sia-icon-question', icon: 'fa-circle-question' },
        logout:   { cls: 'sia-icon-logout',   icon: 'fa-right-from-bracket' }
    };

    // Escape HTML agar aman saat disisipkan
    function esc(str) {
        var d = document.createElement('div');
        d.textContent = (str == null) ? '' : String(str);
        return d.innerHTML;
    }

    // MODAL — mengembalikan Promise<boolean>
    function modal(options) {
        options = options || {};
        var cfg       = ICONS[options.icon] || ICONS.info;
        var title     = options.title || '';
        var text      = options.text || '';
        var confirmTxt = options.confirmText || 'Ya, Lanjut';
        var cancelTxt  = options.cancelText || 'Batal';
        var danger    = !!options.danger;
        var showCancel = options.showCancel !== false;

        return new Promise(function (resolve) {
            var overlay = document.createElement('div');
            overlay.className = 'sia-overlay';
            overlay.innerHTML =
                '<div class="sia-modal" role="dialog" aria-modal="true">' +
                    '<div class="sia-icon ' + cfg.cls + '"><i class="fas ' + cfg.icon + '"></i></div>' +
                    (title ? '<div class="sia-title">' + esc(title) + '</div>' : '') +
                    (text  ? '<div class="sia-text">' + esc(text) + '</div>' : '') +
                    '<div class="sia-buttons">' +
                        (showCancel
                            ? '<button type="button" class="sia-btn sia-btn-cancel" data-sia-action="cancel">' + esc(cancelTxt) + '</button>'
                            : '') +
                        '<button type="button" class="sia-btn sia-btn-confirm' +
                            (danger ? ' sia-btn-danger' : '') +
                            '" data-sia-action="confirm">' + esc(confirmTxt) + '</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(overlay);

            requestAnimationFrame(function () {
                requestAnimationFrame(function () { overlay.classList.add('sia-open'); });
            });

            function close(result) {
                overlay.classList.remove('sia-open');
                overlay.classList.add('sia-close');
                setTimeout(function () {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    resolve(result);
                }, 220);
            }

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) close(false);
            });

            overlay.querySelector('[data-sia-action="confirm"]').addEventListener('click', function () {
                close(true);
            });

            if (showCancel) {
                overlay.querySelector('[data-sia-action="cancel"]').addEventListener('click', function () {
                    close(false);
                });
            }

            var keyHandler = function (e) {
                if (e.key === 'Escape') close(false);
                if (e.key === 'Enter') close(true);
            };
            document.addEventListener('keydown', keyHandler, { once: true });

            // fokus tombol cancel biar tombol enter ga langsung ngejalanin konfirmasi
            var focusBtn = showCancel
                ? overlay.querySelector('.sia-btn-cancel')
                : overlay.querySelector('.sia-btn-confirm');
            if (focusBtn) focusBtn.focus();
        });
    }

    // TOAST — notifikasi modern di pojok kanan atas
    function toast(type, message, title, options) {
        options = options || {};
        var cfg    = ICONS[type] || ICONS.info;
        var autoClose = options.autoClose !== false;
        var duration  = options.duration || 3500;

        var container = document.querySelector('.sia-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'sia-toast-container';
            document.body.appendChild(container);
        }

        var el = document.createElement('div');
        el.className = 'sia-toast sia-toast-' + (ICONS[type] ? type : 'info');
        el.innerHTML =
            '<div class="sia-toast-icon"><i class="fas ' + cfg.icon + '"></i></div>' +
            '<div class="sia-toast-body">' +
                (title ? '<div class="sia-toast-title">' + esc(title) + '</div>' : '') +
                '<div class="sia-toast-msg">' + esc(message) + '</div>' +
            '</div>' +
            '<button type="button" class="sia-toast-close" aria-label="Tutup">&times;</button>' +
            '<div class="sia-toast-progress"></div>';

        container.appendChild(el);

        requestAnimationFrame(function () { el.classList.add('sia-in'); });

        var progress = el.querySelector('.sia-toast-progress');
        progress.style.transition = 'none';
        progress.style.width = '100%';
        void progress.offsetWidth;
        progress.style.transition = 'width ' + duration + 'ms linear';
        progress.style.width = '0%';

        var timer;
        function dismiss() {
            if (el._done) return;
            el._done = true;
            clearTimeout(timer);
            progress.style.transition = 'none';
            el.classList.remove('sia-in');
            setTimeout(function () {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 300);
        }

        el.querySelector('.sia-toast-close').addEventListener('click', dismiss);
        if (autoClose) timer = setTimeout(dismiss, duration);

        return el;
    }

    // HELPERS PRAKTIS

    // Confirm generik → Promise<boolean>
    function siConfirm(options) {
        return modal(options || {});
    }

    // Confirm lalu submit form yang sedang dalam event
    function siConfirmForm(event, options) {
        if (event && event.preventDefault) event.preventDefault();
        var form = null;
        if (event && event.target) {
            form = event.target.form || (event.target.closest ? event.target.closest('form') : null);
        }
        return modal(options || {}).then(function (ok) {
            if (ok && form && form.submit) form.submit();
        });
    }

    // confirm hapus lalu redirect; return false biar aman dipake di <a href> (cegah navigasi default)
    function siHapus(url, nama) {
        var target = appendCsrfToken(url);
        modal({
            icon: 'delete',
            title: 'Yakin ingin menghapus?',
            text: nama
                ? 'Data "' + nama + '" akan dihapus permanen!'
                : 'Data yang dihapus tidak bisa dikembalikan!',
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            danger: true
        }).then(function (ok) {
            if (ok) window.location.href = target;
        });
        return false;
    }

    // confirm logout lalu redirect; return false biar aman di elemen <a>
    function siLogout() {
        var target = appendCsrfToken(LOGOUT_URL);
        modal({
            icon: 'logout',
            title: 'Yakin ingin logout?',
            text: 'Anda akan keluar dari sistem dan perlu login kembali.',
            confirmText: 'Ya, Logout',
            cancelText: 'Batal',
            danger: false
        }).then(function (ok) {
            if (ok) window.location.href = target;
        });
        return false;
    }

    function siToast(type, message, title, options) { return toast(type, message, title, options); }
    function siSuccess(message, title) { return toast('success', message, title || 'Berhasil'); }
    function siError(message, title)    { return toast('error', message, title || 'Gagal'); }
    function siWarning(message, title)  { return toast('warning', message, title || 'Perhatian'); }
    function siInfo(message, title)     { return toast('info', message, title || 'Informasi'); }

    // EXPOSE — global & namespace
    var SIAAlert = {
        modal: modal,
        confirm: siConfirm,
        confirmForm: siConfirmForm,
        hapus: siHapus,
        logout: siLogout,
        toast: siToast,
        success: siSuccess,
        error: siError,
        warning: siWarning,
        info: siInfo
    };

    window.SIAAlert = SIAAlert;
    window.siConfirm = siConfirm;
    window.siConfirmForm = siConfirmForm;
    window.siHapus = siHapus;
    window.siLogout = siLogout;
    window.siToast = siToast;
    window.siSuccess = siSuccess;
    window.siError = siError;
    window.siWarning = siWarning;
    window.siInfo = siInfo;

})(window, document);
