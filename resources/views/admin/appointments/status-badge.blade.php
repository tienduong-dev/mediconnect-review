{{-- Colour per appointment status. Usage: @include('admin.appointments.status-badge', ['status' => $a->status]) --}}
@php
    $tone = match ($status) {
        'Pending'   => 'bg-warning text-dark',
        'Confirmed' => 'bg-primary',
        'Completed' => 'bg-success',
        'Cancelled' => 'bg-secondary',
        'Rejected'  => 'bg-danger',
        'NoShow'    => 'bg-dark',
        default     => 'bg-light text-dark',
    };
@endphp
<span class="badge {{ $tone }}">{{ $status }}</span>
