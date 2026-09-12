@extends('admin/AdminLayout')

@section('page-title', 'Search')

@section('admin-content')
    <div class="page">

        <div class="hello">
            <div>
                <h2>
                    @if ($term === '')
                        Search
                    @else
                        {{ $total }} {{ $total === 1 ? 'result' : 'results' }} for &ldquo;{{ $term }}&rdquo;
                    @endif
                </h2>
                <p>Looks across patients, doctors, appointments, messages and articles at once.</p>
            </div>
        </div>

        @if ($tooShort)
            <div class="card">
                <div class="empty">
                    <i class="bi bi-type"></i>
                    Type at least two characters.
                </div>
            </div>
        @elseif ($term === '')
            <div class="card">
                <div class="card-body">
                    <div class="stat-label mb-3">What you can type</div>
                    <div class="row g-3">
                        @foreach ([
                            ['bi-person', 'A name', 'Nguyen Van A'],
                            ['bi-telephone', 'A phone number', '0912345678'],
                            ['bi-envelope-at', 'An email', 'someone@mail.com'],
                            ['bi-hash', 'An appointment number', 'APT-2026-000001'],
                        ] as $hint)
                            <div class="col-md-6">
                                <div class="hint">
                                    <span class="chip chip-mute"><i class="bi {{ $hint[0] }}"></i></span>
                                    <div class="min-w-0">
                                        <div class="cell-title">{{ $hint[1] }}</div>
                                        <div class="stat-sub">{{ $hint[2] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="helper-note mt-3 mb-0">
                        Typing a full appointment number opens that appointment directly.
                    </div>
                </div>
            </div>
        @elseif ($total === 0)
            <div class="card">
                <div class="empty">
                    <i class="bi bi-search"></i>
                    Nothing matched &ldquo;{{ $term }}&rdquo;.
                    <div class="mt-2">Try part of a name, a phone number, or an email address.</div>
                </div>
            </div>
        @else
            <div class="row g-3">
                @foreach ($groups as $key => $group)
                    <div class="col-xl-6">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center gap-2">
                                <span class="chip chip-{{ $group['tone'] }}" style="width:28px;height:28px;font-size:14px;">
                                    <i class="bi {{ $group['icon'] }}"></i>
                                </span>
                                <h5>{{ $group['label'] }}</h5>
                                <span class="badge bg-secondary ms-1">{{ count($group['rows']) }}</span>
                                <a href="{{ url($group['all']) }}" class="stat-sub ms-auto">See all</a>
                            </div>

                            @foreach ($group['rows'] as $row)
                                <a href="{{ url($row['url']) }}" class="hit">
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="cell-title text-truncate">{{ $row['title'] }}</div>
                                        <div class="stat-sub text-truncate">{{ $row['meta'] }}</div>
                                    </div>
                                    <span class="badge {{ $row['tone'] }} flex-shrink-0">{{ $row['badge'] }}</span>
                                    <i class="bi bi-chevron-right flex-shrink-0"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            .admin-page .hello { margin-bottom: 22px; }
            .admin-page .hello h2 { font-size: 21px; font-weight: 700; letter-spacing: -.4px; margin: 0 0 2px; }
            .admin-page .hello p { margin: 0; font-size: 13px; color: var(--muted); }
            .admin-page .min-w-0 { min-width: 0; }

            .admin-page .hit {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 11px 20px;
                border-bottom: 1px solid var(--line);
                color: var(--ink-2);
                transition: background-color .13s var(--ease), padding-left .13s var(--ease);
            }

            .admin-page .hit:last-child { border-bottom: none; }
            .admin-page .hit:hover { background: var(--surface-2); padding-left: 24px; }
            .admin-page .hit .bi-chevron-right { font-size: 12px; color: var(--muted); }

            .admin-page .hint {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                border: 1px solid var(--line);
                border-radius: var(--r);
                background: var(--surface-2);
            }
        </style>
    @endpush
@endsection
