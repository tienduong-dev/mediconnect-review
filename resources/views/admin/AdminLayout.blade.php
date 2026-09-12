<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="MediConnect">
    <meta name="description" content="MediConnect - Doctor Appointment System, administration area">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('page-title', 'Dashboard') || MediConnect Admin</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo/mediconnect-fav.png') }}">

    {{--
        Theme is applied before the first paint. Running this after the
        stylesheet would show one white frame before switching to dark,
        which reads as a flicker on every page load.
    --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('mc-theme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-theme', saved);
                }
            } catch (e) { /* private mode: fall back to the OS preference */ }
        })();
    </script>

    {{--
        Bootstrap and Bootstrap Icons only.

        The public site template (main.css, animate.css, meanmenu, swiper,
        nice-select, wow.js, GSAP + Lenis + ScrollSmoother) is deliberately
        not loaded: ScrollSmoother wraps the page in a transformed container
        and breaks every sticky element, and main.css restyles tables and
        containers for a marketing page.
    --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">

    <style>
        /* ==========================================================
           Tokens - light
           Surfaces are kept slightly off-white and text stops short of
           pure black, so a full screen of cards does not glare.
           ========================================================== */
        :root {
            --sidebar: 258px;
            --topbar: 64px;

            --bg: #eceff5;
            --surface: #ffffff;
            --surface-2: #f4f6fa;
            --surface-3: #eef1f7;
            --line: #e2e7f0;
            --line-strong: #d3dae6;

            --ink: #1a2231;
            --ink-2: #4a5670;
            --muted: #838ea5;

            --brand: #1668dc;
            --brand-strong: #0f4fa8;
            --brand-soft: #e7f0ff;

            --ok: #0f7a38;        --ok-soft: #e4f4e9;
            --warn: #a86400;      --warn-soft: #fcf2e0;
            --danger: #b92d2d;    --danger-soft: #fceaea;
            --info: #0a6896;      --info-soft: #e3f2fa;
            --neutral-soft: #eaeef4;

            --scrim: rgba(15, 23, 42, .42);
            --shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 1px 3px rgba(16, 24, 40, .05);
            --shadow-lg: 0 8px 24px rgba(16, 24, 40, .10);
            --select-arrow: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%234a5670' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");

            --r-sm: 8px;
            --r: 12px;
            --r-lg: 16px;

            --ease: cubic-bezier(.22, .61, .36, 1);
        }

        /* ==========================================================
           Tokens - dark
           ========================================================== */
        :root[data-theme="dark"] {
            --bg: #0e141f;
            --surface: #161d2b;
            --surface-2: #1b2331;
            --surface-3: #202939;
            --line: #273042;
            --line-strong: #354057;

            --ink: #e7ecf5;
            --ink-2: #adb8cc;
            --muted: #7d8899;

            --brand: #4d9bff;
            --brand-strong: #7ab5ff;
            --brand-soft: #16304f;

            --ok: #59d38a;        --ok-soft: #123424;
            --warn: #e5ad5a;      --warn-soft: #3a2c14;
            --danger: #f0807d;    --danger-soft: #3c1d1e;
            --info: #63c2ec;      --info-soft: #11303e;
            --neutral-soft: #26303f;

            --scrim: rgba(0, 0, 0, .62);
            --shadow: 0 1px 2px rgba(0, 0, 0, .35), 0 1px 3px rgba(0, 0, 0, .3);
            --shadow-lg: 0 10px 28px rgba(0, 0, 0, .5);
            --select-arrow: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23adb8cc' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }

        /* Follow the operating system until the user picks a side */
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0e141f;
                --surface: #161d2b;
                --surface-2: #1b2331;
                --surface-3: #202939;
                --line: #273042;
                --line-strong: #354057;

                --ink: #e7ecf5;
                --ink-2: #adb8cc;
                --muted: #7d8899;

                --brand: #4d9bff;
                --brand-strong: #7ab5ff;
                --brand-soft: #16304f;

                --ok: #59d38a;        --ok-soft: #123424;
                --warn: #e5ad5a;      --warn-soft: #3a2c14;
                --danger: #f0807d;    --danger-soft: #3c1d1e;
                --info: #63c2ec;      --info-soft: #11303e;
                --neutral-soft: #26303f;

                --scrim: rgba(0, 0, 0, .62);
                --shadow: 0 1px 2px rgba(0, 0, 0, .35), 0 1px 3px rgba(0, 0, 0, .3);
                --shadow-lg: 0 10px 28px rgba(0, 0, 0, .5);
                --select-arrow: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23adb8cc' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            }
        }

        * { box-sizing: border-box; }

        html, body { max-width: 100%; overflow-x: hidden; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            transition: background-color .25s var(--ease), color .25s var(--ease);
        }

        a { text-decoration: none; }

        ::selection { background: var(--brand-soft); color: var(--brand-strong); }

        /* Surfaces cross-fade when the theme flips */
        .sb, .tb, .admin-page .card, .admin-page .card-header,
        .admin-page .card-footer, .admin-page .form-control,
        .admin-page .form-select, .admin-page .table thead th {
            transition: background-color .25s var(--ease), border-color .25s var(--ease), color .25s var(--ease);
        }

        /* ==========================================================
           Sidebar
           ========================================================== */
        .sb {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar);
            background: var(--surface);
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            z-index: 1040;
        }

        .sb-brand {
            height: var(--topbar);
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
        }

        .sb-brand a { display: flex; align-items: center; }
        .sb-brand img { height: 30px; width: auto; display: block; }

        /* One artwork per theme: the wordmark is navy on light, white on dark */
        .sb-brand .logo-dark { display: none; }
        :root[data-theme="dark"] .sb-brand .logo-light { display: none; }
        :root[data-theme="dark"] .sb-brand .logo-dark { display: block; }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .sb-brand .logo-light { display: none; }
            :root:not([data-theme="light"]) .sb-brand .logo-dark { display: block; }
        }

        .sb-nav { flex: 1 1 auto; overflow-y: auto; padding: 14px 12px 8px; }
        .sb-nav::-webkit-scrollbar { width: 6px; }
        .sb-nav::-webkit-scrollbar-thumb { background: var(--line-strong); border-radius: 3px; }

        .sb-group {
            padding: 16px 12px 6px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
        }

        .sb-nav a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            margin-bottom: 1px;
            border-radius: var(--r-sm);
            color: var(--ink-2);
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.35;
            transition: background-color .15s var(--ease), color .15s var(--ease), padding-left .15s var(--ease);
        }

        .sb-nav a i {
            font-size: 16px;
            width: 18px;
            flex-shrink: 0;
            text-align: center;
            color: var(--muted);
            transition: color .15s var(--ease), transform .15s var(--ease);
        }

        .sb-nav a:hover { background: var(--surface-2); color: var(--ink); padding-left: 15px; }
        .sb-nav a:hover i { color: var(--ink-2); transform: scale(1.08); }

        .sb-nav a.on { background: var(--brand-soft); color: var(--brand-strong); font-weight: 600; }
        .sb-nav a.on i { color: var(--brand); }

        .sb-nav a.on::before {
            content: "";
            position: absolute;
            left: -12px;
            top: 7px;
            bottom: 7px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--brand);
            animation: barIn .25s var(--ease);
        }

        @keyframes barIn { from { transform: scaleY(0); } to { transform: scaleY(1); } }

        .sb-foot { flex-shrink: 0; padding: 12px; border-top: 1px solid var(--line); }

        .sb-foot a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: var(--r-sm);
            color: var(--ink-2);
            font-size: 13.5px;
            font-weight: 500;
            transition: background-color .15s var(--ease), color .15s var(--ease);
        }

        .sb-foot a:hover { background: var(--danger-soft); color: var(--danger); }

        /* ==========================================================
           Shell
           ========================================================== */
        .shell { margin-left: var(--sidebar); min-height: 100vh; min-width: 0; }

        .tb {
            position: sticky;
            top: 0;
            z-index: 1030;
            height: var(--topbar);
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 26px;
        }

        .tb h1 {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -.2px;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tb-right { margin-left: auto; display: flex; align-items: center; gap: 12px; white-space: nowrap; }

        .theme-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--r-sm);
            border: 1px solid var(--line-strong);
            background: var(--surface-2);
            color: var(--ink-2);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color .15s var(--ease), color .15s var(--ease), transform .15s var(--ease);
        }

        .theme-btn:hover { background: var(--brand-soft); color: var(--brand); transform: rotate(-18deg); }
        .theme-btn:active { transform: scale(.92); }

        /* The moon shows on light, the sun on dark */
        .theme-btn .bi-sun { display: none; }
        :root[data-theme="dark"] .theme-btn .bi-moon-stars { display: none; }
        :root[data-theme="dark"] .theme-btn .bi-sun { display: inline; }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .theme-btn .bi-moon-stars { display: none; }
            :root:not([data-theme="light"]) .theme-btn .bi-sun { display: inline; }
        }

        .tb-user { display: flex; align-items: center; gap: 10px; }
        .tb-user .nm { font-size: 13px; font-weight: 600; color: var(--ink); }
        .tb-user .rl { font-size: 11.5px; color: var(--muted); }

        .tb-user .av {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand) 0%, #64b0ff 100%);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .burger {
            display: none;
            border: 1px solid var(--line-strong);
            background: var(--surface-2);
            border-radius: var(--r-sm);
            width: 36px;
            height: 36px;
            font-size: 18px;
            line-height: 1;
            color: var(--ink);
        }

        .admin-page { padding: 24px 26px 48px; min-width: 0; animation: pageIn .32s var(--ease) both; }

        @keyframes pageIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: none; }
        }

        .admin-page > section,
        .admin-page > .container,
        .admin-page > .container-fluid,
        .admin-page > div.page {
            max-width: 1240px;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
            margin-left: auto;
            margin-right: auto;
        }

        /* ==========================================================
           Cards
           ========================================================== */
        .admin-page .card {
            border: 1px solid var(--line);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow);
            background: var(--surface);
            color: var(--ink);
            overflow: hidden;
        }

        .admin-page .card-header {
            background: var(--surface-2) !important;
            border-bottom: 1px solid var(--line);
            padding: 15px 20px;
            min-height: 0;
            color: var(--ink) !important;
        }

        /* Views written before this redesign ask for dark headers.
           They are normalised to the shared header style. */
        .admin-page .card-header.bg-dark,
        .admin-page .card-header.bg-white { background: var(--surface-2) !important; }
        .admin-page .card-header .text-white { color: var(--ink) !important; }
        .admin-page .card-header .text-white.small { color: var(--muted) !important; }

        .admin-page .card-header h5,
        .admin-page .card-header h6 {
            font-size: 14.5px;
            font-weight: 650;
            letter-spacing: -.1px;
            margin: 0;
            color: var(--ink);
        }

        .admin-page .card-body { padding: 20px; }
        .admin-page .card-body.p-0 { padding: 0; }

        /* Filter bars sit directly under a card header on every list screen */
        .admin-page .card-body.border-bottom {
            background: var(--surface-2);
            border-bottom-color: var(--line) !important;
        }

        .admin-page .card-footer {
            background: var(--surface-2);
            border-top: 1px solid var(--line);
            padding: 12px 20px;
        }

        .admin-page .shadow { box-shadow: var(--shadow) !important; }

        .admin-page .bg-white { background: var(--surface) !important; }

        /* ==========================================================
           Tables
           ========================================================== */
        .admin-page .table-responsive { overflow-x: auto; width: 100%; }

        .admin-page .table {
            margin: 0;
            font-size: 13.5px;
            color: var(--ink-2);
            --bs-table-bg: transparent;
            --bs-table-color: var(--ink-2);
        }

        .admin-page .table > :not(caption) > * > * { background-color: transparent; color: inherit; }

        .admin-page .table thead th {
            background: var(--surface-3);
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            white-space: nowrap;
            border-bottom: 1px solid var(--line);
            padding: .7rem 1rem;
        }

        .admin-page .table td,
        .admin-page .table tbody th {
            vertical-align: middle;
            padding: .8rem 1rem;
            border-color: var(--line);
            /*
               overflow-wrap only breaks a word that genuinely cannot fit.
               word-break: break-word chopped short labels such as "Manage"
               into one letter per line whenever a column got narrow.
            */
            overflow-wrap: break-word;
            word-break: normal;
        }

        .admin-page .table tbody th { color: var(--ink); font-weight: 600; }
        .admin-page .table tbody tr { transition: background-color .12s var(--ease); }
        .admin-page .table tbody tr:hover { background: var(--surface-2); }

        .admin-page .table .cell-title { color: var(--ink); font-weight: 600; }

        .admin-page .table td.text-nowrap,
        .admin-page .table th.text-nowrap { white-space: nowrap; width: 1%; }

        .admin-page .table td .small,
        .admin-page .table td .text-muted { font-size: 12px; color: var(--muted) !important; }

        .admin-page .table-danger,
        .admin-page .table-success { --bs-table-bg: transparent; }
        .admin-page tr.table-danger td, .admin-page tr.table-danger th { background: var(--danger-soft) !important; }
        .admin-page tr.table-success td, .admin-page tr.table-success th { background: var(--ok-soft) !important; }

        /* ==========================================================
           Forms
           ========================================================== */
        .admin-page .form-control,
        .admin-page .form-select {
            height: 40px;
            font-size: 13.5px;
            border-radius: var(--r-sm);
            border: 1px solid var(--line-strong);
            padding: .35rem .75rem;
            background-color: var(--surface);
            color: var(--ink);
        }

        .admin-page .form-select {
            padding-right: 2.2rem;
            background-image: var(--select-arrow);
            background-repeat: no-repeat;
            background-position: right .7rem center;
            background-size: 13px;
        }

        .admin-page .form-select-sm { height: 34px; font-size: 13px; padding-right: 2rem; }
        .admin-page textarea.form-control { height: auto; padding: .6rem .75rem; }
        .admin-page .form-control::placeholder { color: var(--muted); opacity: .85; }

        .admin-page .form-control:focus,
        .admin-page .form-select:focus {
            border-color: var(--brand);
            background-color: var(--surface);
            color: var(--ink);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 22%, transparent);
        }

        .admin-page .form-label { font-size: 12.5px; font-weight: 600; color: var(--ink-2); margin-bottom: .35rem; }
        .admin-page small.text-muted, .admin-page .form-text { font-size: 12px; color: var(--muted) !important; }
        .admin-page .form-check-input { background-color: var(--surface); border-color: var(--line-strong); }
        .admin-page .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }

        .admin-page input[type="date"]::-webkit-calendar-picker-indicator,
        .admin-page input[type="datetime-local"]::-webkit-calendar-picker-indicator { cursor: pointer; }

        :root[data-theme="dark"] .admin-page input[type="date"]::-webkit-calendar-picker-indicator,
        :root[data-theme="dark"] .admin-page input[type="datetime-local"]::-webkit-calendar-picker-indicator,
        :root[data-theme="dark"] .admin-page input[type="file"]::-webkit-file-upload-button { filter: invert(.85); }

        /* ==========================================================
           Buttons
           ========================================================== */
        .admin-page .btn {
            border-radius: var(--r-sm);
            font-size: 13.5px;
            font-weight: 550;
            padding: .48rem .95rem;
            line-height: 1.4;
            white-space: nowrap;
            border-width: 1px;
            transition: background-color .15s var(--ease), border-color .15s var(--ease),
                        color .15s var(--ease), transform .1s var(--ease), box-shadow .15s var(--ease);
        }

        .admin-page .btn:active { transform: translateY(1px); }
        .admin-page .btn-sm { font-size: 12.5px; padding: .34rem .7rem; }
        .admin-page .btn i { margin-right: .35rem; }
        .admin-page .btn-sm i { margin-right: .25rem; }

        .admin-page .btn-primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
            box-shadow: 0 1px 2px color-mix(in srgb, var(--brand) 35%, transparent);
        }

        .admin-page .btn-primary:hover {
            background: var(--brand-strong);
            border-color: var(--brand-strong);
            box-shadow: 0 4px 12px color-mix(in srgb, var(--brand) 38%, transparent);
        }

        .admin-page .btn-outline-primary { color: var(--brand); border-color: color-mix(in srgb, var(--brand) 40%, transparent); background: transparent; }
        .admin-page .btn-outline-primary:hover { background: var(--brand); border-color: var(--brand); color: #fff; }

        .admin-page .btn-outline-secondary { color: var(--ink-2); border-color: var(--line-strong); background: transparent; }
        .admin-page .btn-outline-secondary:hover { background: var(--surface-2); color: var(--ink); border-color: var(--line-strong); }

        .admin-page .btn-outline-danger { color: var(--danger); border-color: color-mix(in srgb, var(--danger) 40%, transparent); background: transparent; }
        .admin-page .btn-outline-danger:hover { background: var(--danger); border-color: var(--danger); color: #fff; }

        .admin-page .btn-outline-dark { color: var(--ink-2); border-color: var(--line-strong); background: transparent; }
        .admin-page .btn-outline-dark:hover { background: var(--ink-2); border-color: var(--ink-2); color: var(--surface); }

        .admin-page .btn-success { background: var(--ok); border-color: var(--ok); color: #fff; }
        .admin-page .btn-light { background: var(--surface); border-color: var(--line-strong); color: var(--ink); }
        .admin-page .btn-light:hover { background: var(--surface-2); }

        .admin-page td .btn + .btn,
        .admin-page td .btn + form,
        .admin-page td form + .btn,
        .admin-page td form + form { margin-left: .3rem; }

        .admin-page td form { display: inline-block; margin: 0; }

        /* ==========================================================
           Badges, tabs, alerts
           ========================================================== */
        .admin-page .badge {
            font-size: 11px;
            padding: .4em .65em;
            font-weight: 650;
            border-radius: 20px;
            letter-spacing: .2px;
        }

        .admin-page .badge i { margin-right: .2rem; font-size: 10px; }

        .admin-page .badge.bg-primary   { background: var(--brand-soft) !important; color: var(--brand-strong) !important; }
        .admin-page .badge.bg-success   { background: var(--ok-soft) !important; color: var(--ok) !important; }
        .admin-page .badge.bg-warning   { background: var(--warn-soft) !important; color: var(--warn) !important; }
        .admin-page .badge.bg-danger    { background: var(--danger-soft) !important; color: var(--danger) !important; }
        .admin-page .badge.bg-secondary { background: var(--neutral-soft) !important; color: var(--ink-2) !important; }
        .admin-page .badge.bg-dark      { background: var(--neutral-soft) !important; color: var(--ink) !important; }
        .admin-page .badge.bg-info      { background: var(--info-soft) !important; color: var(--info) !important; }
        .admin-page .badge.bg-light     { background: var(--neutral-soft) !important; color: var(--ink-2) !important; }

        .admin-page .nav-tabs { border-bottom: 1px solid var(--line); gap: 2px; }

        .admin-page .nav-tabs .nav-link {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-bottom: 2px solid transparent;
            padding: .6rem .9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color .15s var(--ease), border-color .15s var(--ease);
        }

        .admin-page .nav-tabs .nav-link:hover { color: var(--ink); }
        .admin-page .nav-tabs .nav-link.active { color: var(--brand); background: transparent; border-bottom-color: var(--brand); }
        .admin-page .nav-tabs .nav-link .badge { background: var(--neutral-soft) !important; color: var(--ink-2) !important; }
        .admin-page .nav-tabs .nav-link.active .badge { background: var(--brand-soft) !important; color: var(--brand-strong) !important; }

        .admin-page .alert {
            font-size: 13.5px;
            border-radius: var(--r);
            border-width: 1px;
            padding: .85rem 1rem;
            animation: alertIn .3s var(--ease) both;
        }

        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: none; }
        }

        .admin-page .alert-success { background: var(--ok-soft); border-color: color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok); }
        .admin-page .alert-danger  { background: var(--danger-soft); border-color: color-mix(in srgb, var(--danger) 30%, transparent); color: var(--danger); }
        .admin-page .alert-warning { background: var(--warn-soft); border-color: color-mix(in srgb, var(--warn) 30%, transparent); color: var(--warn); }

        :root[data-theme="dark"] .admin-page .btn-close { filter: invert(1) grayscale(1) brightness(2); }

        /* ==========================================================
           Pagination
           ========================================================== */
        .admin-page .pagination { margin: 0; gap: 4px; }

        .admin-page .page-link {
            font-size: 13px;
            padding: .32rem .68rem;
            border-radius: var(--r-sm);
            border: 1px solid var(--line-strong);
            background: var(--surface);
            color: var(--ink-2);
            transition: background-color .13s var(--ease), color .13s var(--ease);
        }

        .admin-page .page-link:hover { background: var(--surface-3); color: var(--ink); }
        .admin-page .page-item.active .page-link { background: var(--brand); border-color: var(--brand); color: #fff; }
        .admin-page .page-item.disabled .page-link { color: var(--muted); background: var(--surface-2); opacity: .6; }

        /* ==========================================================
           Shared pieces
           ========================================================== */
        .admin-page .helper-note {
            font-size: 13px;
            color: var(--brand-strong);
            background: var(--brand-soft);
            border: 1px solid color-mix(in srgb, var(--brand) 28%, transparent);
            border-radius: var(--r);
            padding: .8rem 1rem;
            margin: 0 0 1.1rem;
        }

        .admin-page .sort-link { color: inherit; white-space: nowrap; }
        .admin-page .sort-link:hover { color: var(--ink); }
        .admin-page .sort-link .bi-arrow-down-up { opacity: .35; font-size: 9.5px; }
        .admin-page .sort-link .bi-caret-up-fill,
        .admin-page .sort-link .bi-caret-down-fill { color: var(--brand); font-size: 9.5px; }

        .admin-page .avatar-fallback {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            border-radius: var(--r);
            background: var(--brand-soft);
            color: var(--brand-strong);
            font-size: 44px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-page a.tile-link { color: inherit; display: block; height: 100%; }
        .admin-page a.tile-link .card { transition: box-shadow .18s var(--ease), transform .18s var(--ease), border-color .18s var(--ease); height: 100%; }
        .admin-page a.tile-link:hover .card {
            box-shadow: var(--shadow-lg);
            border-color: color-mix(in srgb, var(--brand) 35%, transparent);
            transform: translateY(-3px);
        }

        .admin-page img { max-width: 100%; height: auto; }

        .admin-page .sec { display: flex; align-items: baseline; gap: 10px; margin: 0 0 12px; }

        .admin-page .sec h2 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0;
        }

        .admin-page .sec .line { flex: 1; height: 1px; background: var(--line); }

        .admin-page .chip {
            width: 40px;
            height: 40px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .admin-page .chip-brand  { background: var(--brand-soft); color: var(--brand); }
        .admin-page .chip-ok     { background: var(--ok-soft); color: var(--ok); }
        .admin-page .chip-warn   { background: var(--warn-soft); color: var(--warn); }
        .admin-page .chip-danger { background: var(--danger-soft); color: var(--danger); }
        .admin-page .chip-info   { background: var(--info-soft); color: var(--info); }
        .admin-page .chip-mute   { background: var(--neutral-soft); color: var(--ink-2); }

        .admin-page .stat-label { font-size: 12px; color: var(--muted); font-weight: 600; letter-spacing: .2px; }
        .admin-page .stat-value { font-size: 26px; font-weight: 700; color: var(--ink); line-height: 1.15; letter-spacing: -.6px; }
        .admin-page .stat-sub { font-size: 12px; color: var(--muted); }

        .admin-page .empty { text-align: center; padding: 34px 20px; color: var(--muted); }
        .admin-page .empty i { font-size: 30px; opacity: .45; display: block; margin-bottom: 8px; }

        .admin-page .text-muted { color: var(--muted) !important; }
        .admin-page code { color: var(--brand-strong); background: var(--surface-3); padding: 1px 5px; border-radius: 5px; }
        .admin-page hr { border-color: var(--line); opacity: 1; }

        /* Cards enter with a short stagger so a dense screen settles
           instead of appearing all at once */
        .admin-page .row > [class*="col-"] { animation: cardIn .34s var(--ease) both; }
        .admin-page .row > [class*="col-"]:nth-child(1) { animation-delay: .02s; }
        .admin-page .row > [class*="col-"]:nth-child(2) { animation-delay: .06s; }
        .admin-page .row > [class*="col-"]:nth-child(3) { animation-delay: .10s; }
        .admin-page .row > [class*="col-"]:nth-child(4) { animation-delay: .14s; }
        .admin-page .row > [class*="col-"]:nth-child(n+5) { animation-delay: .18s; }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: none; }
        }

        /* ==========================================================
           Mobile
           ========================================================== */
        @media (max-width: 991.98px) {
            .sb { transform: translateX(-100%); transition: transform .22s var(--ease); box-shadow: 0 0 34px rgba(0, 0, 0, .28); }
            body.nav-open .sb { transform: translateX(0); }
            .shell { margin-left: 0; }
            .burger { display: inline-flex; align-items: center; justify-content: center; }
            .admin-page { padding: 16px 14px 36px; }
            .tb { padding: 0 14px; }
            .scrim { position: fixed; inset: 0; background: var(--scrim); z-index: 1035; display: none; }
            body.nav-open .scrim { display: block; animation: fadeIn .18s var(--ease); }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        }

        @media (max-width: 575.98px) {
            .tb-user .who { display: none; }
        }

        /* Users who ask the OS for less motion get none of it */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

@php
    $me = auth()->user();

    // Marks the menu entry the current URL belongs to. Patterns are listed
    // per entry because a doctor profile, the add-profile form and the edit
    // form all belong to the Doctors section.
    $on = fn (...$p) => request()->is(...$p) ? 'on' : '';
@endphp

<aside class="sb">
    <div class="sb-brand">
        <a href="{{ url('Admin/AdminDashboard') }}">
            <img src="{{ asset('assets/img/logo/mediconnect-h.png') }}" alt="MediConnect" class="logo-light">
            <img src="{{ asset('assets/img/logo/mediconnect-h-white.png') }}" alt="MediConnect" class="logo-dark">
        </a>
    </div>

    <nav class="sb-nav">
        <a href="{{ url('Admin/AdminDashboard') }}" class="{{ $on('Admin/AdminDashboard') }}">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="{{ url('Admin/Appointments') }}" class="{{ $on('Admin/Appointments', 'Admin/Appointments/*') }}">
            <i class="bi bi-calendar2-check"></i><span>Appointments</span>
        </a>

        <div class="sb-group">People</div>
        <a href="{{ url('Admin/Patients') }}" class="{{ $on('Admin/Patients', 'Admin/Patients/*') }}">
            <i class="bi bi-people"></i><span>Patients</span>
        </a>
        <a href="{{ url('Admin/Doctors') }}"
            class="{{ $on('Admin/Doctors', 'Admin/DoctorProfile/*', 'Admin/AdminDoctorDetailsForm/*', 'Admin/Doctor/EditThisProfile/*') }}">
            <i class="bi bi-person-badge"></i><span>Doctors</span>
        </a>
        <a href="{{ url('Admin/DoctorRegister') }}" class="{{ $on('Admin/DoctorRegister') }}">
            <i class="bi bi-person-plus"></i><span>Add Doctor</span>
        </a>

        <div class="sb-group">Master data</div>
        <a href="{{ url('Admin/Cities') }}" class="{{ $on('Admin/Cities', 'Admin/Cities/*') }}">
            <i class="bi bi-geo-alt"></i><span>Cities</span>
        </a>
        <a href="{{ url('Admin/Facilities') }}" class="{{ $on('Admin/Facilities', 'Admin/Facilities/*') }}">
            <i class="bi bi-hospital"></i><span>Facilities</span>
        </a>
        <a href="{{ url('Admin/Specializations') }}" class="{{ $on('Admin/Specializations', 'Admin/Specializations/*') }}">
            <i class="bi bi-clipboard2-pulse"></i><span>Specializations</span>
        </a>
        <a href="{{ url('Admin/FacilitySpecializations') }}"
            class="{{ $on('Admin/FacilitySpecializations', 'Admin/FacilitySpecializations/*') }}">
            <i class="bi bi-diagram-3"></i><span>Facility services</span>
        </a>
        <a href="{{ url('Admin/DoctorAssignments') }}"
            class="{{ $on('Admin/DoctorAssignments', 'Admin/DoctorAssignments/*') }}">
            <i class="bi bi-person-vcard"></i><span>Doctor assignments</span>
        </a>

        <div class="sb-group">Content</div>
        <a href="{{ url('Admin/MedicalContents') }}" class="{{ $on('Admin/MedicalContents', 'Admin/MedicalContents/*') }}">
            <i class="bi bi-journal-medical"></i><span>Medical contents</span>
        </a>
        <a href="{{ url('Admin/ContactMessages') }}" class="{{ $on('Admin/ContactMessages', 'Admin/ContactMessages/*') }}">
            <i class="bi bi-envelope"></i><span>Contact messages</span>
        </a>
    </nav>

    <div class="sb-foot">
        <a href="{{ url('logout') }}">
            <i class="bi bi-box-arrow-right"></i><span>Sign out</span>
        </a>
    </div>
</aside>

<div class="scrim" data-nav-close></div>

<div class="shell">

    <header class="tb">
        <button type="button" class="burger" data-nav-toggle aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>

        <h1>@yield('page-title', 'Dashboard')</h1>

        <div class="tb-right">
            <button type="button" class="theme-btn" data-theme-toggle aria-label="Switch theme" title="Switch theme">
                <i class="bi bi-moon-stars"></i>
                <i class="bi bi-sun"></i>
            </button>

            <div class="tb-user">
                <div class="who text-end">
                    <div class="nm">{{ $me->name ?? 'Administrator' }}</div>
                    <div class="rl">Administrator</div>
                </div>
                <span class="av">{{ strtoupper(mb_substr($me->name ?? 'A', 0, 1)) }}</span>
            </div>
        </div>
    </header>

    <main class="admin-page">
        @yield('admin-content')
    </main>
</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script>
    (function () {
        var root = document.documentElement;
        var body = document.body;

        /* ---- Theme ----
           No stored value means "follow the operating system". The first
           click writes an explicit choice, which then wins over the OS. */
        function currentTheme() {
            var set = root.getAttribute('data-theme');
            if (set) return set;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.querySelectorAll('[data-theme-toggle]').forEach(function (el) {
            el.addEventListener('click', function () {
                var next = currentTheme() === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('mc-theme', next); } catch (e) { /* ignore */ }
            });
        });

        /* ---- Sidebar drawer on small screens ---- */
        document.querySelectorAll('[data-nav-toggle]').forEach(function (el) {
            el.addEventListener('click', function () { body.classList.toggle('nav-open'); });
        });

        document.querySelectorAll('[data-nav-close]').forEach(function (el) {
            el.addEventListener('click', function () { body.classList.remove('nav-open'); });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { body.classList.remove('nav-open'); }
        });
    })();
</script>

@stack('scripts')

</body>

</html>
