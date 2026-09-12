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
        Only Bootstrap and Bootstrap Icons are loaded here.

        The public site template (main.css, animate.css, meanmenu, swiper,
        nice-select, wow.js, GSAP + Lenis + ScrollSmoother) used to be pulled
        into this layout as well. ScrollSmoother wraps the page in a
        transformed container and wow.js hides elements until they scroll
        into view, which is what pushed admin tables out of their cards and
        left blocks overlapping. None of the admin screens use any of it, so
        the whole set is gone.
    --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">

    <style>
        :root {
            --sidebar-width: 252px;
            --topbar-height: 58px;
            --ink: #1f2937;
            --muted: #64748b;
            --line: #e3e8f0;
            --canvas: #f4f6fa;
            --navy: #1e2c47;
            --navy-soft: #2a3b57;
            --brand: #1f6feb;
        }

        * { box-sizing: border-box; }

        html, body { max-width: 100%; overflow-x: hidden; }

        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ============ Sidebar ============ */

        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--navy);
            display: flex;
            flex-direction: column;
            z-index: 1040;
        }

        .admin-brand {
            padding: 18px 16px 14px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            flex-shrink: 0;
        }

        .admin-brand img { max-width: 190px; width: 100%; height: auto; }

        /* Only the menu scrolls, so the logo and the sign-out row stay put */
        .admin-nav {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 12px 10px;
        }

        .admin-nav::-webkit-scrollbar { width: 6px; }
        .admin-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .16); border-radius: 3px; }

        .admin-nav .group-label {
            display: block;
            padding: 14px 12px 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .9px;
            text-transform: uppercase;
            color: #8ea2c0;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            margin-bottom: 2px;
            border-radius: 8px;
            color: #d7e0ee;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.3;
            transition: background .12s, color .12s;
        }

        .admin-nav a i {
            font-size: 16px;
            width: 18px;
            flex-shrink: 0;
            text-align: center;
        }

        .admin-nav a span { min-width: 0; }

        .admin-nav a:hover { background: rgba(255, 255, 255, .07); color: #fff; }

        .admin-nav a.active {
            background: var(--brand);
            color: #fff;
            font-weight: 600;
        }

        .admin-signout {
            flex-shrink: 0;
            padding: 10px;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .admin-signout a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px;
            border-radius: 8px;
            color: #ffb4b4;
            text-decoration: none;
            font-size: 14px;
        }

        .admin-signout a:hover { background: rgba(255, 90, 90, .14); color: #fff; }

        /* ============ Main column ============ */

        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
        }

        .admin-topbar h1 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-user {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            color: var(--muted);
            white-space: nowrap;
        }

        .admin-user .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-toggle {
            display: none;
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            font-size: 18px;
            line-height: 1;
            color: var(--ink);
        }

        .admin-page {
            flex: 1 1 auto;
            padding: 22px 20px 40px;
            min-width: 0;
        }

        /*
            Every screen shares one content width, so the layout does not
            jump when moving between pages. Some views open with
            <section class="container"> and others with "container-fluid";
            both are normalised here instead of editing 18 files.
        */
        .admin-page > section,
        .admin-page > .container,
        .admin-page > .container-fluid {
            max-width: 1180px;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
            margin-left: auto;
            margin-right: auto;
        }

        /* ============ Components ============ */

        .admin-page .card {
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
            background: #fff;
            overflow: hidden;
        }

        .admin-page .card-header {
            background: var(--navy-soft) !important;
            border-bottom: none;
            padding: 13px 18px;
            min-height: 0;
        }

        .admin-page .card-header h5,
        .admin-page .card-header h6 { font-size: 15px; font-weight: 600; margin: 0; }

        .admin-page .card-header.bg-white { background: #fff !important; border-bottom: 1px solid var(--line); }

        .admin-page .card-body { padding: 18px; }
        .admin-page .card-body.p-0 { padding: 0; }

        .admin-page .card-footer {
            background: #fbfcfe;
            border-top: 1px solid var(--line);
            padding: 13px 18px;
        }

        /* Tables scroll inside their own card instead of widening the page */
        .admin-page .table-responsive { overflow-x: auto; width: 100%; }

        .admin-page .table { margin: 0; font-size: 14px; }

        .admin-page .table thead th {
            background: #f6f8fc;
            color: #5a6b86;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
            border-bottom: 1px solid var(--line);
            padding: .65rem .85rem;
        }

        .admin-page .table td,
        .admin-page .table tbody th {
            vertical-align: middle;
            padding: .7rem .85rem;
            border-color: #eef2f7;
        }

        .admin-page .table tbody tr:hover { background: #f9fbfe; }

        .admin-page .table td .small,
        .admin-page .table td .text-muted { font-size: 12.5px; }

        .admin-page .form-control,
        .admin-page .form-select {
            height: 40px;
            font-size: 14px;
            border-radius: 8px;
            border: 1px solid #d6dee9;
            padding: .35rem .7rem;
            background-color: #fff;
            color: var(--ink);
        }

        .admin-page textarea.form-control { height: auto; padding: .55rem .7rem; }

        .admin-page .form-control:focus,
        .admin-page .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(31, 111, 235, .14);
        }

        .admin-page .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #47566d;
            margin-bottom: .3rem;
        }

        .admin-page .form-control + small,
        .admin-page .form-select + small,
        .admin-page small.text-muted { font-size: 12.5px; }

        .admin-page .btn {
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            padding: .45rem .9rem;
            line-height: 1.4;
            white-space: nowrap;
        }

        .admin-page .btn-sm { font-size: 13px; padding: .33rem .65rem; }
        .admin-page .btn i { margin-right: .3rem; }

        /* Buttons sitting side by side in a table cell keep a gap even
           when one of them is wrapped in its own <form> */
        .admin-page td .btn + .btn,
        .admin-page td .btn + form,
        .admin-page td form + .btn,
        .admin-page td form + form { margin-left: .35rem; }

        .admin-page td form { display: inline-block; margin: 0; }

        .admin-page .badge {
            font-size: 11.5px;
            padding: .42em .7em;
            font-weight: 600;
            border-radius: 6px;
        }

        .admin-page .badge i { margin-right: .2rem; }

        .admin-page .nav-tabs { border-bottom: 1px solid var(--line); }

        .admin-page .nav-tabs .nav-link {
            color: var(--muted);
            font-size: 13.5px;
            font-weight: 600;
            border: none;
            border-bottom: 2px solid transparent;
            padding: .55rem .9rem;
        }

        .admin-page .nav-tabs .nav-link:hover { color: var(--ink); }

        .admin-page .nav-tabs .nav-link.active {
            color: var(--brand);
            background: transparent;
            border-bottom-color: var(--brand);
        }

        .admin-page .alert { font-size: 14px; border-radius: 9px; }

        .admin-page .pagination { margin: 0; }
        .admin-page .page-link { font-size: 13.5px; padding: .35rem .65rem; }

        .admin-page .helper-note {
            font-size: 13px;
            color: var(--muted);
            background: #eef4ff;
            border: 1px solid #d6e4ff;
            border-radius: 9px;
            padding: .7rem .9rem;
            margin: 0 0 1rem;
        }

        .admin-page .sort-link { color: inherit; text-decoration: none; white-space: nowrap; }
        .admin-page .sort-link:hover { color: var(--ink); }
        .admin-page .sort-link .bi-arrow-down-up { opacity: .3; font-size: 10px; }
        .admin-page .sort-link .bi-caret-up-fill,
        .admin-page .sort-link .bi-caret-down-fill { color: var(--brand); font-size: 10px; }

        /* Initials shown when a doctor has no usable photo file */
        .admin-page .avatar-fallback {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            border-radius: 12px;
            background: #eef2f8;
            color: #5c6b85;
            font-size: 44px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-page a.tile-link { text-decoration: none; color: inherit; display: block; height: 100%; }
        .admin-page a.tile-link .card { transition: box-shadow .14s, transform .14s; height: 100%; }
        .admin-page a.tile-link:hover .card {
            box-shadow: 0 5px 14px rgba(16, 24, 40, .11);
            transform: translateY(-1px);
        }

        .admin-page img { max-width: 100%; height: auto; }

        /* Long values must wrap instead of stretching the table */
        .admin-page .table td { word-break: break-word; }
        .admin-page .table td.text-nowrap { word-break: normal; }

        /* ============ Mobile ============ */

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform .2s ease;
                box-shadow: 0 0 30px rgba(0, 0, 0, .3);
            }

            body.sidebar-open .admin-sidebar { transform: translateX(0); }

            .admin-main { margin-left: 0; }

            .sidebar-toggle { display: inline-flex; align-items: center; justify-content: center; }

            .admin-page { padding: 16px 14px 32px; }

            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, .45);
                z-index: 1035;
                display: none;
            }

            body.sidebar-open .sidebar-backdrop { display: block; }
        }

        @media (max-width: 575.98px) {
            .admin-user .who { display: none; }
            .admin-topbar { padding: 0 12px; }
        }
    </style>
</head>

<body>

@php
    $adminUser = auth()->user();

    // Highlights the menu entry the current URL belongs to. Patterns are
    // listed per entry because a doctor profile, the add-profile form and
    // the edit form all live under the Doctors section.
    $isOn = fn (...$patterns) => request()->is(...$patterns) ? 'active' : '';
@endphp

<aside class="admin-sidebar">
    <div class="admin-brand">
        <a href="{{ url('Admin/AdminDashboard') }}">
            <img src="{{ asset('assets/img/logo/mediconnect-white.png') }}" alt="MediConnect">
        </a>
    </div>

    <nav class="admin-nav">
        <a href="{{ url('Admin/AdminDashboard') }}" class="{{ $isOn('Admin/AdminDashboard') }}">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a href="{{ url('Admin/Appointments') }}" class="{{ $isOn('Admin/Appointments', 'Admin/Appointments/*') }}">
            <i class="bi bi-calendar2-check"></i><span>Appointments</span>
        </a>

        <span class="group-label">People</span>
        <a href="{{ url('Admin/Patients') }}" class="{{ $isOn('Admin/Patients', 'Admin/Patients/*') }}">
            <i class="bi bi-people"></i><span>Patients</span>
        </a>
        <a href="{{ url('Admin/Doctors') }}"
            class="{{ $isOn('Admin/Doctors', 'Admin/DoctorProfile/*', 'Admin/AdminDoctorDetailsForm/*', 'Admin/Doctor/EditThisProfile/*') }}">
            <i class="bi bi-person-badge"></i><span>Doctors</span>
        </a>
        <a href="{{ url('Admin/DoctorRegister') }}" class="{{ $isOn('Admin/DoctorRegister') }}">
            <i class="bi bi-person-plus"></i><span>Add Doctor</span>
        </a>

        <span class="group-label">Master Data</span>
        <a href="{{ url('Admin/Cities') }}" class="{{ $isOn('Admin/Cities', 'Admin/Cities/*') }}">
            <i class="bi bi-geo-alt"></i><span>Cities</span>
        </a>
        <a href="{{ url('Admin/Facilities') }}" class="{{ $isOn('Admin/Facilities', 'Admin/Facilities/*') }}">
            <i class="bi bi-hospital"></i><span>Facilities</span>
        </a>
        <a href="{{ url('Admin/Specializations') }}" class="{{ $isOn('Admin/Specializations', 'Admin/Specializations/*') }}">
            <i class="bi bi-clipboard2-pulse"></i><span>Specializations</span>
        </a>
        <a href="{{ url('Admin/FacilitySpecializations') }}"
            class="{{ $isOn('Admin/FacilitySpecializations', 'Admin/FacilitySpecializations/*') }}">
            <i class="bi bi-diagram-3"></i><span>Facility Specializations</span>
        </a>
        <a href="{{ url('Admin/DoctorAssignments') }}"
            class="{{ $isOn('Admin/DoctorAssignments', 'Admin/DoctorAssignments/*') }}">
            <i class="bi bi-person-vcard"></i><span>Doctor Assignments</span>
        </a>

        <span class="group-label">Content</span>
        <a href="{{ url('Admin/MedicalContents') }}" class="{{ $isOn('Admin/MedicalContents', 'Admin/MedicalContents/*') }}">
            <i class="bi bi-journal-medical"></i><span>Medical Contents</span>
        </a>
        <a href="{{ url('Admin/ContactMessages') }}" class="{{ $isOn('Admin/ContactMessages', 'Admin/ContactMessages/*') }}">
            <i class="bi bi-envelope"></i><span>Contact Messages</span>
        </a>
    </nav>

    <div class="admin-signout">
        <a href="{{ url('logout') }}">
            <i class="bi bi-box-arrow-right"></i><span>Sign out</span>
        </a>
    </div>
</aside>

<div class="sidebar-backdrop" data-sidebar-close></div>

<div class="admin-main">

    <header class="admin-topbar">
        <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>

        <h1>@yield('page-title', 'Dashboard')</h1>

        <div class="admin-user">
            <span class="who">{{ $adminUser->name ?? 'Administrator' }}</span>
            <span class="avatar">{{ strtoupper(mb_substr($adminUser->name ?? 'A', 0, 1)) }}</span>
        </div>
    </header>

    <main class="admin-page">
        @yield('admin-content')
    </main>
</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script>
    // Sidebar drawer on small screens
    (function () {
        var body = document.body;

        document.querySelectorAll('[data-sidebar-toggle]').forEach(function (el) {
            el.addEventListener('click', function () {
                body.classList.toggle('sidebar-open');
            });
        });

        document.querySelectorAll('[data-sidebar-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { body.classList.remove('sidebar-open'); }
        });
    })();
</script>

</body>

</html>
