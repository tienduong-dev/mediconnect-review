@extends('admin/AdminLayout')

@section('page-title', 'Availability')

@section('admin-content')
    @php
        $tone = [
            'Available' => ['ok', 'bi-unlock', 'Open for booking'],
            'Held'      => ['warn', 'bi-hourglass', 'A patient is mid-booking'],
            'Booked'    => ['brand', 'bi-check2-circle', 'Taken'],
            'Blocked'   => ['danger', 'bi-slash-circle', 'Closed by the doctor'],
        ];
    @endphp

    <div class="page">

        @include('admin.partials.flash')

        <div class="helper-note">
            Read only. Opening hours and slot generation belong to the Doctor module &mdash; this screen answers
            <em>why</em> a patient can or cannot book, it does not change availability.
        </div>

        {{-- ============ Why patients cannot book ============ --}}
        @if (count($coverage['starved']) > 0)
            @php
                $why = [
                    'never'   => ['danger', 'bi-calendar-x',     'Never opened',   'The doctor has not created a weekly schedule, so no slot was ever generated.'],
                    'expired' => ['warn',   'bi-hourglass-bottom','All in the past','Slots exist but every one of them has already passed. The schedule needs extending.'],
                    'taken'   => ['info',   'bi-people-fill',    'Fully booked',   'Future slots exist but every one is held, booked or blocked.'],
                ];
            @endphp

            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="chip chip-danger" style="width:28px;height:28px;font-size:14px;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </span>
                    <h5>
                        {{ count($coverage['starved']) }} of {{ $coverage['total'] }} active assignments
                        cannot be booked
                    </h5>
                    <span class="stat-sub ms-auto">{{ $coverage['covered'] }} bookable</span>
                </div>

                <div class="card-body">
                    {{-- The cause matters more than the list: each one needs a
                         different fix, and they look identical to a patient. --}}
                    <div class="row g-2 mb-3">
                        {{-- Distinct names on purpose: $tone holds the slot
                             status map that the day grid below still needs. --}}
                        @foreach ($why as $key => [$causeTone, $causeIcon, $causeLabel, $causeHint])
                            @continue($coverage['reasons'][$key] === 0)
                            <div class="col-md-4">
                                <div class="cause cause-{{ $causeTone }}">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="chip chip-{{ $causeTone }}"
                                            style="width:26px;height:26px;font-size:12px;">
                                            <i class="bi {{ $causeIcon }}"></i>
                                        </span>
                                        <span class="cell-title">
                                            {{ $coverage['reasons'][$key] }} &middot; {{ $causeLabel }}
                                        </span>
                                    </div>
                                    <div class="stat-sub">{{ $causeHint }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <details class="starved-list">
                        <summary>Show the {{ count($coverage['starved']) }} affected assignments</summary>
                        <div class="row g-2 mt-2">
                            @foreach ($coverage['starved'] as $row)
                                @php($fs = $row['assignment']->facilitySpecialization)
                                <div class="col-md-6">
                                    <a href="{{ url('Admin/Availability') }}?assignment_id={{ $row['assignment']->id }}"
                                        class="starved">
                                        <span class="chip chip-{{ $why[$row['reason']][0] }}">
                                            <i class="bi {{ $why[$row['reason']][1] }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="cell-title text-truncate">
                                                {{ $row['assignment']->doctor->user->name ?? 'Unknown doctor' }}
                                            </div>
                                            <div class="stat-sub text-truncate">
                                                {{ $fs->facility->name ?? '?' }} &middot; {{ $fs->specialization->name ?? '?' }}
                                                @if ($row['last_date'])
                                                    &middot; last slot
                                                    {{ \Illuminate\Support\Carbon::parse($row['last_date'])->format('d/m/Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
            </div>
        @endif

        {{-- ============ Picker ============ --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ url('Admin/Availability') }}" class="row g-2 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label">Doctor and workplace</label>
                        <select name="assignment_id" class="form-select" onchange="this.form.submit()">
                            @forelse ($assignments as $a)
                                <option value="{{ $a->id }}" @selected($selected && $selected->id === $a->id)>
                                    {{ $a->doctor->user->name ?? 'Unknown doctor' }}
                                    &nbsp;|&nbsp;
                                    {{ $a->facilitySpecialization->facility->name ?? '?' }}
                                    &mdash;
                                    {{ $a->facilitySpecialization->specialization->name ?? '?' }}
                                    @if ($a->room_number) (Room {{ $a->room_number }}) @endif
                                </option>
                            @empty
                                <option value="">No active assignment exists</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Week starting</label>
                        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
                    </div>

                    <div class="col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1"><i class="bi bi-arrow-repeat"></i> Show</button>
                    </div>
                </form>

                @if ($selected)
                    <div class="d-flex flex-wrap gap-3 mt-3 pt-3" style="border-top:1px solid var(--line);">
                        @foreach ($tone as $status => [$chip, $icon, $hint])
                            <div class="legend-item" title="{{ $hint }}">
                                <span class="chip chip-{{ $chip }}" style="width:26px;height:26px;font-size:12px;">
                                    <i class="bi {{ $icon }}"></i>
                                </span>
                                <span class="lb">{{ $status }}</span>
                                <span class="vl">{{ $totals[$status] }}</span>
                            </div>
                        @endforeach

                        <div class="ms-auto d-flex gap-2">
                            <a href="{{ url('Admin/Availability') }}?assignment_id={{ $selected->id }}&from={{ $from->copy()->subDays(7)->toDateString() }}"
                                class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i> Previous</a>
                            <a href="{{ url('Admin/Availability') }}?assignment_id={{ $selected->id }}&from={{ $from->copy()->addDays(7)->toDateString() }}"
                                class="btn btn-sm btn-outline-secondary">Next <i class="bi bi-chevron-right ms-1"></i></a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if (!$selected)
            <div class="card">
                <div class="empty">
                    <i class="bi bi-person-vcard"></i>
                    No active doctor assignment exists yet.
                    <div class="mt-2">
                        <a href="{{ url('Admin/DoctorAssignments') }}" class="btn btn-sm btn-outline-primary">
                            Assign a doctor
                        </a>
                    </div>
                </div>
            </div>
        @else

            {{-- ============ Seven day grid ============ --}}
            <div class="week">
                @foreach ($days as $day)
                    @php($dayKey = $day->toDateString())
                    @php($dayslots = $slots[$dayKey] ?? collect())
                    <div class="day {{ $day->isToday() ? 'is-today' : '' }} {{ $day->isPast() && !$day->isToday() ? 'is-past' : '' }}">
                        <div class="day-head">
                            <div class="dow">{{ $day->format('D') }}</div>
                            <div class="dom">{{ $day->format('d/m') }}</div>
                            @if ($day->isToday())
                                <span class="badge bg-primary">today</span>
                            @endif
                        </div>

                        <div class="day-body">
                            @forelse ($dayslots as $slot)
                                @php($t = $tone[$slot->status] ?? ['mute', 'bi-question', $slot->status])
                                <div class="slot slot-{{ $t[0] }}"
                                    title="{{ $slot->status }}{{ $slot->blocked_reason ? ' — ' . $slot->blocked_reason : '' }}">
                                    <i class="bi {{ $t[1] }}"></i>
                                    <span>{{ substr((string) $slot->start_time, 0, 5) }}</span>
                                </div>
                            @empty
                                <div class="no-slot">&mdash;</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ============ Opening hours behind the slots ============ --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Opening hours on file</h5>
                </div>

                @if ($schedules->isEmpty())
                    <div class="empty">
                        <i class="bi bi-calendar2-x"></i>
                        This assignment has no weekly schedule, so no slot can be generated for it.
                    </div>
                @else
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered align-middle m-0">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Hours</th>
                                    <th>Slot length</th>
                                    <th>Valid</th>
                                    <th class="text-center">State</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedules as $s)
                                    <tr>
                                        <td class="cell-title">{{ $s->day }}</td>
                                        <td class="text-nowrap">
                                            {{ substr((string) $s->start_time, 0, 5) }}
                                            &ndash;
                                            {{ substr((string) $s->end_time, 0, 5) }}
                                        </td>
                                        <td>{{ $s->slot_duration_minutes }} min</td>
                                        <td class="text-nowrap">
                                            {{ $s->valid_from ? \Illuminate\Support\Carbon::parse($s->valid_from)->format('Y-m-d') : 'always' }}
                                            &rarr;
                                            {{ $s->valid_until ? \Illuminate\Support\Carbon::parse($s->valid_until)->format('Y-m-d') : 'no end' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $s->is_available ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $s->is_available ? 'Available' : 'Paused' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            .admin-page .min-w-0 { min-width: 0; }

            .admin-page .starved {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                border: 1px solid var(--line);
                border-radius: var(--r);
                background: var(--surface-2);
                color: var(--ink-2);
                transition: border-color .15s var(--ease), transform .15s var(--ease);
            }

            .admin-page .starved:hover {
                border-color: color-mix(in srgb, var(--danger) 45%, transparent);
                transform: translateY(-1px);
            }

            .admin-page .cause {
                padding: 11px 13px;
                border: 1px solid var(--line);
                border-radius: var(--r);
                background: var(--surface-2);
                height: 100%;
            }

            .admin-page .cause-danger { border-color: color-mix(in srgb, var(--danger) 32%, transparent); }
            .admin-page .cause-warn   { border-color: color-mix(in srgb, var(--warn) 32%, transparent); }
            .admin-page .cause-info   { border-color: color-mix(in srgb, var(--info) 32%, transparent); }

            .admin-page .starved-list summary {
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                color: var(--brand);
                list-style: none;
            }

            .admin-page .starved-list summary::-webkit-details-marker { display: none; }
            .admin-page .starved-list summary::before { content: "\25B8"; margin-right: 6px; display: inline-block; transition: transform .15s var(--ease); }
            .admin-page .starved-list[open] summary::before { transform: rotate(90deg); }

            .admin-page .legend-item { display: flex; align-items: center; gap: 7px; }
            .admin-page .legend-item .lb { font-size: 12.5px; color: var(--muted); }
            .admin-page .legend-item .vl { font-size: 13.5px; font-weight: 700; color: var(--ink); }

            /* Seven columns that collapse to a scrollable strip on mobile */
            .admin-page .week {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 10px;
            }

            .admin-page .day {
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: var(--r-lg);
                overflow: hidden;
                min-height: 150px;
                display: flex;
                flex-direction: column;
            }

            .admin-page .day.is-today { border-color: var(--brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 14%, transparent); }
            .admin-page .day.is-past { opacity: .55; }

            .admin-page .day-head {
                padding: 10px 12px;
                border-bottom: 1px solid var(--line);
                background: var(--surface-2);
                display: flex;
                align-items: baseline;
                gap: 6px;
                flex-wrap: wrap;
            }

            .admin-page .day-head .dow { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
            .admin-page .day-head .dom { font-size: 13px; font-weight: 600; color: var(--ink); }

            .admin-page .day-body { padding: 8px; display: flex; flex-direction: column; gap: 5px; }

            .admin-page .slot {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
                font-weight: 600;
                padding: 5px 7px;
                border-radius: 7px;
                cursor: default;
            }

            .admin-page .slot i { font-size: 11px; }
            .admin-page .slot-ok     { background: var(--ok-soft); color: var(--ok); }
            .admin-page .slot-warn   { background: var(--warn-soft); color: var(--warn); }
            .admin-page .slot-brand  { background: var(--brand-soft); color: var(--brand-strong); }
            .admin-page .slot-danger { background: var(--danger-soft); color: var(--danger); }
            .admin-page .slot-mute   { background: var(--neutral-soft); color: var(--ink-2); }

            .admin-page .no-slot { text-align: center; color: var(--muted); padding: 14px 0; font-size: 13px; }

            @media (max-width: 991.98px) {
                .admin-page .week { grid-template-columns: repeat(7, 132px); overflow-x: auto; padding-bottom: 6px; }
            }
        </style>
    @endpush
@endsection
