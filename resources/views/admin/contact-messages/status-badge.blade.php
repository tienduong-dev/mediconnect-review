{{--
    Status pill for a contact message.
    Usage: @include('admin.contact-messages.status-badge', ['status' => $m->status])
--}}
@php
    $map = [
        'New'        => ['bg-primary', 'bi-envelope', 'New'],
        'InProgress' => ['bg-warning text-dark', 'bi-hourglass-split', 'In progress'],
        'Resolved'   => ['bg-success', 'bi-check2-circle', 'Resolved'],
        'Spam'       => ['bg-dark', 'bi-slash-circle', 'Spam'],
    ];

    [$class, $icon, $label] = $map[$status] ?? ['bg-light text-dark', 'bi-question', $status];
@endphp

<span class="badge {{ $class }}"><i class="bi {{ $icon }}"></i> {{ $label }}</span>
