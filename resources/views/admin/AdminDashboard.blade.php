@extends('admin/AdminLayout')

@section('page-title', 'Dashboard')

@section('admin-content')
    @php
        $me = auth()->user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        // Pipeline segments, ordered the way an appointment actually moves
        $pipeline = [
            ['key' => 'Pending',   'label' => 'Pending',   'value' => $appointments['pending'],   'color' => '#e8a33d'],
            ['key' => 'Confirmed', 'label' => 'Confirmed', 'value' => $appointments['confirmed'], 'color' => '#1668dc'],
            ['key' => 'Completed', 'label' => 'Completed', 'value' => $appointments['completed'], 'color' => '#10893e'],
            ['key' => 'Cancelled', 'label' => 'Cancelled', 'value' => $appointments['cancelled'], 'color' => '#98a2b3'],
            ['key' => 'Rejected',  'label' => 'Rejected',  'value' => $appointments['rejected'],  'color' => '#d3564f'],
            ['key' => 'NoShow',    'label' => 'No show',   'value' => $appointments['no_show'],   'color' => '#6b7480'],
        ];

        $pipelineTotal = max(1, array_sum(array_column($pipeline, 'value')));

        $chain = [
            ['label' => 'Cities',          'value' => $masterData['cities'],          'url' => 'Admin/Cities',           'icon' => 'bi-geo-alt'],
            ['label' => 'Facilities',      'value' => $masterData['facilities'],      'url' => 'Admin/Facilities',       'icon' => 'bi-hospital'],
            ['label' => 'Specializations', 'value' => $masterData['specializations'], 'url' => 'Admin/Specializations',  'icon' => 'bi-clipboard2-pulse'],
            ['label' => 'Assignments',     'value' => $masterData['assignments'],     'url' => 'Admin/DoctorAssignments','icon' => 'bi-person-vcard'],
            ['label' => 'Open slots',      'value' => $masterData['open_slots'],      'url' => null,                     'icon' => 'bi-clock-history'],
        ];

        $alerts = [];
        if ($accounts['doctors_no_profile'] > 0) {
            $alerts[] = [
                'text' => $accounts['doctors_no_profile'] . ' doctor account(s) have no professional profile yet.',
                'cta'  => ['Review doctors', 'Admin/Doctors'],
                'tone' => 'warn',
            ];
        }
        if ($masterData['assignments'] === 0) {
            $alerts[] = [
                'text' => 'No active doctor assignment exists, so patient search returns nothing.',
                'cta'  => ['Assign a doctor', 'Admin/DoctorAssignments'],
                'tone' => 'danger',
            ];
        }
        if ($masterData['open_slots'] === 0) {
            $alerts[] = [
                'text' => 'No available slot exists, so patients cannot book any appointment.',
                'cta'  => null,
                'tone' => 'danger',
            ];
        }
    @endphp

    <div class="page">

        @include('admin.partials.flash')

        {{-- ============ Greeting ============ --}}
        <div class="hello">
            <div>
                <h2>{{ $greeting }}, {{ explode(' ', trim($me->name ?? 'Admin'))[0] }}</h2>
                <p>{{ now()->format('l, d F Y') }} &middot; here is what needs your attention today.</p>
            </div>
            <a href="{{ url('Admin/Appointments') }}?status=Pending" class="btn btn-primary">
                <i class="bi bi-inbox"></i> Review pending queue
            </a>
        </div>

        {{-- ============ Headline numbers ============ --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <a href="{{ url('Admin/Appointments') }}?status=Pending" class="tile-link">
                    <div class="card">
                        <div class="card-body d-flex align-items-start gap-3">
                            <span class="chip chip-warn"><i class="bi bi-hourglass-split"></i></span>
                            <div class="min-w-0">
                                <div class="stat-label">Awaiting your action</div>
                                <div class="stat-value">{{ $appointments['pending'] }}</div>
                                <div class="stat-sub">pending requests</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-xl-3">
                <a href="{{ url('Admin/Appointments') }}" class="tile-link">
                    <div class="card">
                        <div class="card-body d-flex align-items-start gap-3">
                            <span class="chip chip-brand"><i class="bi bi-calendar2-event"></i></span>
                            <div class="min-w-0">
                                <div class="stat-label">Scheduled today</div>
                                <div class="stat-value">{{ $appointments['today'] }}</div>
                                <div class="stat-sub">of {{ $appointments['total'] }} total</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-xl-3">
                <a href="{{ url('Admin/Doctors') }}" class="tile-link">
                    <div class="card">
                        <div class="card-body d-flex align-items-start gap-3">
                            <span class="chip chip-ok"><i class="bi bi-person-badge"></i></span>
                            <div class="min-w-0">
                                <div class="stat-label">Active doctors</div>
                                <div class="stat-value">{{ $accounts['doctors_active'] }}</div>
                                <div class="stat-sub">of {{ $accounts['doctors_total'] }} accounts</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-xl-3">
                <a href="{{ url('Admin/Patients') }}" class="tile-link">
                    <div class="card">
                        <div class="card-body d-flex align-items-start gap-3">
                            <span class="chip chip-info"><i class="bi bi-people"></i></span>
                            <div class="min-w-0">
                                <div class="stat-label">Active patients</div>
                                <div class="stat-value">{{ $accounts['patients_active'] }}</div>
                                <div class="stat-sub">of {{ $accounts['patients_total'] }} accounts</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- ============ Needs attention ============ --}}
        @if (count($alerts))
            <div class="sec"><h2>Needs attention</h2><span class="line"></span></div>

            <div class="row g-3 mb-4">
                @foreach ($alerts as $alert)
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 attention attention-{{ $alert['tone'] }}">
                            <div class="card-body d-flex gap-3">
                                <span class="chip chip-{{ $alert['tone'] }}">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="mb-2">{{ $alert['text'] }}</p>
                                    @if ($alert['cta'])
                                        <a href="{{ url($alert['cta'][1]) }}" class="btn btn-sm btn-outline-primary">
                                            {{ $alert['cta'][0] }} <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ============ Pipeline + queue ============ --}}
        <div class="sec"><h2>Appointment pipeline</h2><span class="line"></span></div>

        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Status breakdown</h5>
                        <span class="stat-sub">{{ $appointments['total'] }} appointments</span>
                    </div>
                    <div class="card-body">

                        {{-- One bar; each segment is sized by its share of the total --}}
                        <div class="bar mb-3">
                            @foreach ($pipeline as $seg)
                                @if ($seg['value'] > 0)
                                    <span style="width: {{ round($seg['value'] / $pipelineTotal * 100, 2) }}%; background: {{ $seg['color'] }};"
                                        title="{{ $seg['label'] }}: {{ $seg['value'] }}"></span>
                                @endif
                            @endforeach
                        </div>

                        <div class="legend">
                            @foreach ($pipeline as $seg)
                                <a href="{{ url('Admin/Appointments') }}?status={{ $seg['key'] }}" class="leg">
                                    <span class="dot" style="background: {{ $seg['color'] }}"></span>
                                    <span class="lb">{{ $seg['label'] }}</span>
                                    <span class="vl">{{ $seg['value'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Waiting for approval</h5>
                        <a href="{{ url('Admin/Appointments') }}?status=Pending" class="stat-sub">View all</a>
                    </div>

                    @forelse ($pendingQueue as $row)
                        <div class="queue-row">
                            <span class="chip chip-warn"><i class="bi bi-clock"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="cell-title text-truncate">{{ $row->patient_name }}</div>
                                <div class="stat-sub">{{ $row->appointment_number }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="cell-title">{{ substr((string) $row->start_time, 0, 5) }}</div>
                                <div class="stat-sub">
                                    {{ \Illuminate\Support\Carbon::parse($row->appointment_date)->format('d M') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty">
                            <i class="bi bi-check2-circle"></i>
                            Nothing is waiting for approval.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ============ Booking chain ============ --}}
        <div class="sec"><h2>Booking chain</h2><span class="line"></span></div>

        <div class="helper-note">
            Patients can only reach a doctor when every link below has data:
            city &rarr; facility &rarr; specialization &rarr; assignment &rarr; open slot.
            A zero anywhere in the chain breaks search for everyone.
        </div>

        <div class="row g-3 mb-4">
            @foreach ($chain as $item)
                <div class="col-6 col-md-4 col-xl">
                    @if ($item['url'])
                        <a href="{{ url($item['url']) }}" class="tile-link">
                    @endif

                    <div class="card h-100 {{ $item['value'] === 0 ? 'zero' : '' }}">
                        <div class="card-body text-center py-4">
                            <span class="chip {{ $item['value'] === 0 ? 'chip-danger' : 'chip-mute' }} mx-auto mb-2">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </span>
                            <div class="stat-value">{{ $item['value'] }}</div>
                            <div class="stat-label">{{ $item['label'] }}</div>
                        </div>
                    </div>

                    @if ($item['url'])
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ============ Inbox shortcut ============ --}}
        <a href="{{ url('Admin/ContactMessages') }}?status=New" class="tile-link">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="chip {{ $newContactMessages > 0 ? 'chip-brand' : 'chip-mute' }}">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="cell-title">
                            {{ $newContactMessages }} new contact
                            {{ $newContactMessages === 1 ? 'message' : 'messages' }}
                        </div>
                        <div class="stat-sub">Sent from the public contact form.</div>
                    </div>
                    <i class="bi bi-arrow-right ms-auto text-muted"></i>
                </div>
            </div>
        </a>
    </div>

    @push('styles')
        <style>
            .admin-page .hello {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                margin-bottom: 22px;
            }

            .admin-page .hello h2 {
                font-size: 22px;
                font-weight: 700;
                letter-spacing: -.5px;
                margin: 0 0 2px;
                color: var(--ink);
            }

            .admin-page .hello p { margin: 0; font-size: 13px; color: var(--muted); }

            .admin-page .min-w-0 { min-width: 0; }

            .admin-page .attention p { font-size: 13px; color: var(--ink-2); }
            .admin-page .attention-warn { border-color: #f1dcb4; }
            .admin-page .attention-danger { border-color: #f3cfcf; }

            /* Single stacked bar: one segment per status */
            .admin-page .bar {
                display: flex;
                height: 12px;
                border-radius: 20px;
                overflow: hidden;
                background: var(--neutral-soft);
            }

            .admin-page .bar span { display: block; height: 100%; }
            .admin-page .bar span + span { border-left: 2px solid #fff; }

            .admin-page .legend {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 4px;
            }

            .admin-page .leg {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 7px 9px;
                border-radius: var(--r-sm);
                color: var(--ink-2);
            }

            .admin-page .leg:hover { background: var(--surface-2); color: var(--ink); }
            .admin-page .leg .dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
            .admin-page .leg .lb { font-size: 13px; }
            .admin-page .leg .vl { margin-left: auto; font-weight: 700; font-size: 13.5px; color: var(--ink); }

            .admin-page .queue-row {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 20px;
                border-bottom: 1px solid var(--line);
            }

            .admin-page .queue-row:last-child { border-bottom: none; }
            .admin-page .queue-row:hover { background: #fafbfe; }
            .admin-page .queue-row .chip { width: 34px; height: 34px; font-size: 15px; }

            /* A zero anywhere in the booking chain is a blocker, not a statistic */
            .admin-page .card.zero { border-color: #f3cfcf; background: #fffafa; }
            .admin-page .card.zero .stat-value { color: var(--danger); }
        </style>
    @endpush
@endsection
