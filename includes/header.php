<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (function_exists('csrf_meta')) echo csrf_meta(); ?>
    <title>
        <?= isset($title)
            ? e($title) . ' - SMAN 4 Palopo'
            : 'SIA SMAN 4 Palopo' ?>
    </title>

    <link rel="icon" type="image/png"
          href="/siakad/assets/img/logo-sekolah.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
          href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet"
          href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<link rel="stylesheet" href="/siakad/assets/css/style.css?v=10.8">

    <link rel="stylesheet" href="/siakad/assets/css/alert.css?v=1.0">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            font-family: 'Roboto', sans-serif !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body<?= !empty($body_class) ? ' class="' . e($body_class) . '"' : '' ?>>
      <?php if(isset($_SESSION['user_id'])): ?>

<?php endif; ?>

    <!-- Mobile sidebar toggle + overlay (global untuk semua halaman) -->
    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu">
        <i class="bi bi-list"></i>
    </button>

    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <script>
        (function () {
            function ready(fn) {
                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
                else fn();
            }

            ready(function () {
                var btn = document.getElementById('sidebarToggle');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    var sidebar = document.getElementById('sidebar');
                    var overlay = document.getElementById('sidebarOverlay');
                    if (!sidebar) return;

                    sidebar.classList.toggle('show');
                    if (overlay) overlay.classList.toggle('show');
                });
            });
        })();
    </script>