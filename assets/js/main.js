/* ================================================================
   SIA SMA NEGERI 4 PALOPO — main.js
   ================================================================ */

/* ── Konfirmasi Logout (Alert System modern) ── */
function konfirmasiLogout() {
    siLogout();
}

/* ── Konfirmasi Hapus (Alert System modern) ── */
function konfirmasiHapus(url, nama) {
    siHapus(url, nama);
}

/* ── CSRF: sisipkan token ke semua form agar submit aman ── */
function initCsrfInjection() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return;
    var token = meta.getAttribute('content');
    if (!token) return;

    document.querySelectorAll('form').forEach(function (form) {
        if (form.querySelector('input[name="csrf_token"]')) return;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token;
        form.appendChild(input);
    });
}


/* ── Auto-hide alert .alert-auto setelah 3 detik ── */
function initAlertAutohide() {
    var alerts = document.querySelectorAll('.alert-auto');
    if (!alerts.length) return;

    setTimeout(function () {
        alerts.forEach(function (el) {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity    = '0';
            setTimeout(function () {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 500);
        });
    }, 3000);
}


/* ── Sidebar toggle (mobile) ── */
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;

    sidebar.classList.toggle('show');
    if (overlay) overlay.classList.toggle('show');
}

/* ── Sidebar collapse (desktop) ── */
function toggleSidebarCollapse() {
    var body = document.body;
    body.classList.toggle('sidebar-collapsed');

    var btn = document.getElementById('sidebarCollapseBtn');
    if (btn) {
        btn.title = body.classList.contains('sidebar-collapsed') ? 'Tampilkan Menu' : 'Sembunyikan Menu';
    }

    try {
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0');
    } catch (e) {}
}

(function initSidebarCollapse() {
    var body = document.body;
    var collapsed = false;
    try { collapsed = localStorage.getItem('sidebarCollapsed') === '1'; } catch (e) {}

    if (collapsed) body.classList.add('sidebar-collapsed');

    var btn = document.getElementById('sidebarCollapseBtn');
    if (btn) {
        btn.title = collapsed ? 'Tampilkan Menu' : 'Sembunyikan Menu';
    }
})();

/* ════════════════════════════════════════════════════════════════
   NOTIFIKASI — toggle dropdown, tandai dibaca, update badge
   ════════════════════════════════════════════════════════════════ */
var NOTIF_API = '/siakad/auth/notification-api.php';

/* Backdrop notifikasi (mobile): dibuat saat dropdown terbuka di layar kecil,
   diklik untuk menutup; dihapus otomatis saat dropdown ditutup. */
function isMobileLayout() {
    return window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
}

function buatNotifBackdrop(dropdown) {
    if (!isMobileLayout()) return;
    if (document.querySelector('.notif-backdrop')) return; // sudah ada
    var bd = document.createElement('div');
    bd.className = 'notif-backdrop';
    bd.addEventListener('click', function () {
        dropdown.classList.remove('show');
        hapusNotifBackdrop();
    });
    document.body.appendChild(bd);
}

function hapusNotifBackdrop() {
    var bd = document.querySelector('.notif-backdrop');
    if (bd) bd.parentNode.removeChild(bd);
}

function initNotifikasi() {
    var toggle  = document.getElementById('notificationToggle');
    var dropdown = document.getElementById('notificationDropdown');
    if (!toggle || !dropdown) return;

    /* Toggle dropdown (+ backdrop semi-transparan di mobile) */
    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        var akanBuka = !dropdown.classList.contains('show');
        if (akanBuka) {
            buatNotifBackdrop(dropdown);
        } else {
            hapusNotifBackdrop();
        }
        dropdown.classList.toggle('show');
    });

    /* Tandai dibaca saat klik item (kalau link tidak kosong) */
    var items = dropdown.querySelectorAll('.notif-item[data-id]');
    items.forEach(function (item) {
        item.addEventListener('click', function (e) {
            var id = item.getAttribute('data-id');
            if (!id) return;
            item.classList.remove('unread');
            var dot = item.querySelector('.notif-dot');
            if (dot) dot.remove();
            fetch(appendCsrfToUrl(NOTIF_API + '?aksi=read&id=' + id))
                .then(function (r) { return r.json(); })
                .then(function (d) { updateNotifBadge(d.count); })
                .catch(function () {});
        });
    });

    /* Tandai semua dibaca */
    var readAll = document.getElementById('notifReadAll');
    if (readAll) {
        readAll.addEventListener('click', function (e) {
            e.stopPropagation();
            fetch(appendCsrfToUrl(NOTIF_API + '?aksi=read_all'))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    updateNotifBadge(0);
                    dropdown.querySelectorAll('.notif-item.unread').forEach(function (it) {
                        it.classList.remove('unread');
                        var dot = it.querySelector('.notif-dot');
                        if (dot) dot.remove();
                    });
                    readAll.remove();
                })
                .catch(function () {});
        });
    }
}

/* Tambahkan param csrf_token ke URL (dipakai panggilan AJAX yang mengubah data) */
function appendCsrfToUrl(url) {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return url;
    var token = meta.getAttribute('content');
    if (!token) return url;
    var sep = url.indexOf('?') === -1 ? '?' : '&';
    return url + sep + 'csrf_token=' + encodeURIComponent(token);
}

function updateNotifBadge(count) {
    var badge = document.getElementById('notifBadge');
    if (!badge) return;
    count = parseInt(count, 10) || 0;
    if (count <= 0) {
        if (badge.parentNode) badge.parentNode.removeChild(badge);
    } else {
        badge.textContent = count > 99 ? '99+' : count;
    }
}


/* ── jQuery ready ── */
$(document).ready(function () {

    /* CSRF: sisipkan token ke semua form */
    initCsrfInjection();

    /* Init DataTables — cek dulu supaya tidak reinitialise */
    if ($.fn.DataTable && $('.dataTable').length) {
        $('.dataTable').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                var exportExcel = $(this).data('export') === 'excel';

                var $tableEl = $(this);

                /* Empty-state: baris "<td colspan=N>" tunggal di tbody membuat
                   DataTables error TN/18 "Incorrect column count". Pindahkan
                   isinya ke language.emptyTable supaya DataTables merender
                   pesan kosong dengan colspan yang benar. */
                var emptyHtml = null;
                $tableEl.find('tbody > tr').each(function () {
                    var $td = $(this).children('td[colspan]');
                    var $headTh = $tableEl.find('thead th');
                    if ($td.length === 1 && $headTh.length &&
                        parseInt($td.attr('colspan'), 10) === $headTh.length) {
                        emptyHtml = $td.html();
                        $(this).remove();
                    }
                });

                var dtOptions = {
                    retrieve: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    },
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    order: [],
                    dom: exportExcel ? 'Bfrtip' : 'lfrtip',
                    /* Rapikan kontrol DataTables (dropdown "Tampilkan X entri"
                       dan kotak pencarian) dengan class Bootstrap */
                    initComplete: function () {
                        var $container = $(this.api().table().container());
                        $container.find('.dataTables_length select').addClass('form-select form-select-sm');
                        $container.find('.dataTables_filter input').addClass('form-control form-control-sm');
                    },
                    buttons: exportExcel ? [{
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel"></i> Export Excel',
                        className: 'btn btn-success btn-sm mb-2',
                        title: function () {
                            // Nama file otomatis ikut kelas yang sedang difilter (kalau ada)
                            var baseTitle  = $tableEl.data('export-title') || 'Data Export';
                            var $filterKls = $('.table-filter-select[data-table="#' + $tableEl.attr('id') + '"]');
                            var kelasAktif = $filterKls.length ? $filterKls.val() : '';
                            return kelasAktif ? baseTitle + ' - Kelas ' + kelasAktif : baseTitle;
                        },
                        exportOptions: {
                            columns: ':not(:last-child)', // kolom "Aksi" tidak ikut diexport
                            search: 'applied',            // hanya export baris yang sedang difilter (mis. per kelas)
                            order: 'applied'               // urutan hasil export ikut urutan tabel saat ini
                        }
                    }] : []
                };
                if (emptyHtml) {
                    dtOptions.language.emptyTable = emptyHtml;
                }

                $(this).DataTable(dtOptions);
            }
        });
    }

    /* ── Indikator tabel responsif mobile (BUG.MD) ──
       - Class .has-overflow : tabel lebih lebar dari viewport → CSS
         menampilkan gradien "geser ke kanan" + scrollbar tebal
       - Class .scrolled-end : sudah discroll sampai ujung kanan →
         gradien memudar (tidak ada kolom tersembunyi lagi) */
    function updateTableOverflow() {
        document.querySelectorAll('.table-responsive').forEach(function (el) {
            var overflow = el.scrollWidth > el.clientWidth + 1;
            var atEnd = !overflow || (el.scrollLeft + el.clientWidth >= el.scrollWidth - 2);
            el.classList.toggle('has-overflow', overflow);
            el.classList.toggle('scrolled-end', atEnd);
        });
    }

    document.querySelectorAll('.table-responsive').forEach(function (el) {
        el.addEventListener('scroll', updateTableOverflow, { passive: true });
    });

    updateTableOverflow();
    setTimeout(updateTableOverflow, 300);
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateTableOverflow, 150);
    });

    /* ── Filter tabel berdasarkan kolom tertentu (dropdown) ── */
    $('.table-filter-select').on('change', function () {
        var $table       = $($(this).data('table'));
        var columnIndex  = $(this).data('column');
        if (!$table.length || $.fn.DataTable.isDataTable($table) === false) return;

        $table.DataTable().column(columnIndex).search(this.value).draw();
    });

    /* ── Urutkan tabel berdasarkan kolom tertentu (dropdown) ──
       value dropdown formatnya "kolom:arah", contoh "2:asc" atau "3:desc" */
    $('.table-sort-select').on('change', function () {
        var $table = $($(this).data('table'));
        if (!$table.length || $.fn.DataTable.isDataTable($table) === false) return;
        if (!this.value) return;

        var parts       = this.value.split(':');
        var columnIndex = parseInt(parts[0], 10);
        var direction   = parts[1] || 'asc';

        $table.DataTable().order([columnIndex, direction]).draw();
    });

    /* Bootstrap tooltips */
    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
    });

    /* Alert autohide */
    initAlertAutohide();

    /* Notifikasi */
    initNotifikasi();

    /* Tutup dropdown notifikasi saat klik di luar (backdrop ikut dihapus) */
    $(document).on('click', function (e) {
        var $dropdown = $('#notificationDropdown');
        var $toggle   = $('#notificationToggle');
        if ($dropdown.length && $toggle.length &&
            !$dropdown[0].contains(e.target) &&
            !$toggle[0].contains(e.target)) {
            $dropdown.removeClass('show');
            hapusNotifBackdrop();
        }
    });

    /* Tutup sidebar saat klik overlay */
    $('#sidebarOverlay').on('click', function () {
        $('#sidebar').removeClass('show');
        $(this).removeClass('show');
    });

    /* ── Pertahankan posisi scroll menu sidebar antar halaman ── */
    (function () {
        var KEY = 'sidebarMenuScroll';

        function saveSidebarScroll() {
            try {
                var $menu = $('.sidebar-menu');
                if ($menu.length) sessionStorage.setItem(KEY, $menu.scrollTop());
            } catch (e) {}
        }

        function restoreSidebarScroll() {
            try {
                var saved = sessionStorage.getItem(KEY);
                if (saved !== null) $('.sidebar-menu').scrollTop(parseInt(saved, 10));
            } catch (e) {}
        }

        $(document).on('click', '.sidebar .nav-link[href]', saveSidebarScroll);
        restoreSidebarScroll();
    })();

    /* ── Pencarian Global (Topbar Search) ── */
    (function () {
        var $input = $('#topbarSearch');
        if (!$input.length) return;

        // Bangun index menu dari sidebar
        function buildIndex() {
            var items = [];
            $('.sidebar .nav-link[href]').each(function () {
                var $link = $(this);
                var text  = ($link.find('span').first().text() || '').trim();
                if (!text) return;
                items.push({
                    label: text,
                    href: $link.attr('href'),
                    $link: $link
                });
            });
            return items;
        }

        // Buat container dropdown hasil pencarian
        var $wrap  = $input.closest('.topbar-search');
        if ($wrap.length && !$wrap.find('.search-results').length) {
            $wrap.append('<div class="search-results"></div>');
        }
        var $results = $wrap.find('.search-results');

        function showResults(items, q) {
            if (!items.length) {
                $results.html('<div class="search-no-result">Tidak ada hasil untuk "' + q + '"</div>');
            } else {
                var html = items.map(function (it) {
                    return '<a href="' + it.href + '" class="search-result-item">' +
                           '<i class="bi bi-arrow-right-short"></i>' +
                           '<span>' + it.label + '</span></a>';
                }).join('');
                $results.html(html);
            }
            $results.addClass('show');
        }

        function hideResults() {
            $results.removeClass('show');
        }

        // Juga filter menu sidebar secara live
        function filterSidebar(items, q) {
            items.forEach(function (it) {
                var match = !q || it.label.toLowerCase().indexOf(q) >= 0;
                it.$link.closest('a, li, div').first().toggle(match);
                it.$link.toggle(match);
            });
        }

        var lastQ = '';
        $input.on('input', function () {
            var q = $.trim($input.val()).toLowerCase();
            lastQ = q;

            var items = buildIndex();
            var hits  = q ? items.filter(function (it) {
                return it.label.toLowerCase().indexOf(q) >= 0;
            }) : [];

            if (q) {
                showResults(hits.slice(0, 8), q);
                filterSidebar(items, q);
            } else {
                hideResults();
                filterSidebar(items, '');
            }
        });

        // Navigasi hasil & tutup
        $results.on('click', '.search-result-item', function (e) {
            e.preventDefault();
            window.location.href = $(this).attr('href');
        });

        // Tutup saat klik di luar
        $(document).on('click', function (e) {
            if ($wrap.length && !$wrap[0].contains(e.target)) {
                hideResults();
            }
        });

        // Enter → buka hasil pertama
        $input.on('keydown', function (e) {
            if (e.key === 'Enter') {
                var $first = $results.find('.search-result-item').first();
                if ($first.length) {
                    e.preventDefault();
                    window.location.href = $first.attr('href');
                }
            }
            if (e.key === 'Escape') {
                hideResults();
                $input.val('');
                filterSidebar(buildIndex(), '');
            }
        });

        // Inisialisasi awal: pastikan sidebar semua terlihat
        filterSidebar(buildIndex(), '');
    })();

    /* ── Zoom kontrol diagram struktur organisasi ── */
    function initOrgZoom() {
        var stages = document.querySelectorAll('.org-stage');
        for (var i = 0; i < stages.length; i++) {
            (function (stage) {
                var wrap = stage.parentElement;
                if (!wrap) return;
                var val = wrap.querySelector('[data-org-zoom-val]');
                var scale = 1;
                function applyScale() {
                    stage.style.transform = 'scale(' + scale + ')';
                    if (val) val.textContent = Math.round(scale * 100) + '%';
                }
                var buttons = wrap.querySelectorAll('[data-org-zoom]');
                for (var b = 0; b < buttons.length; b++) {
                    (function (btn) {
                        btn.addEventListener('click', function () {
                            var act = btn.getAttribute('data-org-zoom');
                            if (act === 'in') scale = Math.min(1.6, +(scale + 0.1).toFixed(2));
                            else if (act === 'out') scale = Math.max(0.5, +(scale - 0.1).toFixed(2));
                            else scale = 1;
                            applyScale();
                        });
                    })(buttons[b]);
                }
            })(stages[i]);
        }
    }

    initOrgZoom();

});