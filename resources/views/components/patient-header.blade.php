 <!-- Header Section Start -->
    <header id="header-sticky" class="header-1 header-style2">
        <div class="container">
            <div class="mega-menu-wrapper">
                <div class="header-main style-2">
                    <div class="header-left">
                        <div class="logo">
                            <a href="{{ url('Patient/PatientDashboard') }}" class="header-logo">
                                <img src="{{ asset('assets/img/logo/mediconnect-full.png') }}" alt="logo-img" width="150px">
                            </a>
                        </div>
                    </div>
                    <div class="header-right d-flex justify-content-end align-items-center">
                        <div class="mean__menu-wrapper">
                            <div class="main-menu">
                                <nav id="mobile-menu">
                                    <ul>
                                        <li><a href="{{ url('Patient/PatientDashboard') }}">Home</a></li>
                                        <li><a href="{{ url('about') }}">About Us</a></li>
                                        <li><a href="{{ url('doctors') }}">Doctor</a></li>
                                        <li><a href="{{ route('patient.appointments') }}">My Appointments</a></li>
                                        <li>
                                            <a href="{{ route('patient.notifications') }}">

                                                Notifications

                                                @if (
                                                    auth()->check() &&
                                                    auth()->user()->unreadNotifications->count() > 0
                                                )

                                                    <span class="badge bg-danger ms-1">

                                                        {{ auth()->user()
                                                            ->unreadNotifications
                                                            ->count() }}

                                                    </span>

                                                @endif

                                            </a>
                                        </li>
                                        <li><a href="{{ route('patient.medical.contents') }}">Medical Knowledge</a></li>
                                        <li><a href="{{ url('contact') }}">Contact</a></li>
                                        @if (auth()->user())
                                            <li class="has-dropdown"><a href="{{ url('logout') }}"
                                                    style="color:#f98c8c">Logout</a></li>
                                        @else
                                            <li class="has-dropdown"><a href="{{ url('login') }}">Login</a></li>
                                            <li class="has-dropdown dark"><a href="{{ url('register') }}">Register</a>
                                            </li>
                                        @endif
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        {{-- <a href="#0" class="search-trigger search-icon d-none d-xl-block"><i
                                class="fal fa-search"></i></a> --}}
                        <a href="{{ url('Patient/MyProfile') }}" class="profile-icon"><i class="fas fa-user"></i></a>
                        {{-- Profile canvas --}}
                        {{-- <a href="{{ url('/Patient/PatientDashboard') }}"
                            class="profile-trigger search-icon d-none d-xl-block ms-0">
                            <i class="fas fa-user"></i>
                        </a> --}}
                        <div class="header__hamburger d-xl-none my-auto">
                            <div class="sidebar__toggle">
                                <img src="{{ asset('assets/img/icon/menu.png') }}" alt="icon">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>