@extends('admin/AdminLayout')

@section('page-title', 'My Account')

@section('admin-content')
    <div class="page">

        @include('admin.partials.flash')

        <div class="row g-3">

            {{-- Identity card --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center py-4">
                        <div class="me-avatar">{{ strtoupper(mb_substr($admin->name, 0, 1)) }}</div>
                        <div class="stat-value mt-3" style="font-size:19px;">{{ $admin->name }}</div>
                        <div class="stat-sub">{{ $admin->email }}</div>

                        <span class="badge bg-primary mt-2">
                            <i class="bi bi-shield-lock"></i> Administrator
                        </span>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Account</span>
                            <span class="badge {{ $admin->account_status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $admin->account_status }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between small mt-2">
                            <span class="text-muted">Last sign in</span>
                            <span>{{ optional($admin->last_login_at)->format('Y-m-d H:i') ?: 'Never' }}</span>
                        </div>
                        <div class="d-flex justify-content-between small mt-2">
                            <span class="text-muted">Member since</span>
                            <span>{{ optional($admin->created_at)->format('Y-m-d') }}</span>
                        </div>

                        <a href="{{ url('Admin/MyAccount/Admins') }}" class="btn btn-outline-secondary btn-sm w-100 mt-4">
                            <i class="bi bi-people"></i> Who else is an admin
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">

                {{-- Profile --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Profile</h5>
                    </div>
                    <form method="POST" action="{{ url('Admin/MyAccount/Profile') }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" maxlength="255"
                                        value="{{ old('name', $admin->name) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile number <span class="text-danger">*</span></label>
                                    <input type="tel" name="number" class="form-control" pattern="[0-9]{10}"
                                        maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        value="{{ old('number', $admin->number) }}" required>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" maxlength="255"
                                    value="{{ old('email', $admin->email) }}" required>
                                <small class="text-muted">This is the address you sign in with.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary"><i class="bi bi-save"></i> Save profile</button>
                        </div>
                    </form>
                </div>

                {{-- Password --}}
                <div class="card">
                    <div class="card-header">
                        <h5>Password</h5>
                    </div>
                    <form method="POST" action="{{ url('Admin/MyAccount/Password') }}"
                        onsubmit="return confirm('Change your password? Every other session signed in as you will be closed.')">
                        @csrf
                        <div class="card-body">

                            <div class="helper-note">
                                Changing your password closes every other session signed in as you &mdash; on another
                                browser, another computer, or one you forgot to sign out of.
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Current password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control" required
                                    autocomplete="current-password">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" id="newPassword" class="form-control"
                                        minlength="8" required autocomplete="new-password">
                                    <div class="meter mt-2"><span id="meterBar"></span></div>
                                    <small class="text-muted" id="meterText">At least 8 characters.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Repeat new password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        minlength="8" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary"><i class="bi bi-key"></i> Change password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .admin-page .me-avatar {
                width: 82px;
                height: 82px;
                margin: 0 auto;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--brand) 0%, #64b0ff 100%);
                color: #fff;
                font-size: 34px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .admin-page .meter {
                height: 5px;
                border-radius: 20px;
                background: var(--neutral-soft);
                overflow: hidden;
            }

            .admin-page .meter span {
                display: block;
                height: 100%;
                width: 0;
                background: var(--danger);
                transition: width .2s var(--ease), background-color .2s var(--ease);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Purely advisory. The rule that actually blocks a weak password
            // is the min:8 check on the server.
            (function () {
                var input = document.getElementById('newPassword');
                var bar = document.getElementById('meterBar');
                var text = document.getElementById('meterText');
                if (!input) return;

                input.addEventListener('input', function () {
                    var v = input.value, score = 0;
                    if (v.length >= 8) score++;
                    if (v.length >= 12) score++;
                    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                    if (/\d/.test(v)) score++;
                    if (/[^A-Za-z0-9]/.test(v)) score++;

                    var levels = [
                        [0, '#b92d2d', 'Too short.'],
                        [25, '#b92d2d', 'Weak.'],
                        [45, '#a86400', 'Fair.'],
                        [65, '#a86400', 'Good.'],
                        [85, '#0f7a38', 'Strong.'],
                        [100, '#0f7a38', 'Very strong.']
                    ];

                    var lv = levels[Math.min(score, levels.length - 1)];
                    bar.style.width = (v.length ? lv[0] : 0) + '%';
                    bar.style.background = lv[1];
                    text.textContent = v.length ? lv[2] : 'At least 8 characters.';
                });
            })();
        </script>
    @endpush
@endsection
