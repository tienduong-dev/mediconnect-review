{{--
    Status pill for a medical content row.
    Usage: @include('admin.medical-contents.status-badge', ['content' => $c])
    "Scheduled" is not a database status: it is a Published row whose
    published_at is still in the future, so visitors cannot see it yet.
--}}
@php
    $scheduled = $content->status === 'Published'
        && $content->published_at
        && $content->published_at->isFuture();

    $map = [
        'Draft'     => ['bg-secondary', 'bi-pencil', 'Draft'],
        'Published' => ['bg-success', 'bi-globe', 'Published'],
        'Archived'  => ['bg-dark', 'bi-archive', 'Archived'],
    ];

    [$class, $icon, $label] = $scheduled
        ? ['bg-warning text-dark', 'bi-clock', 'Scheduled']
        : ($map[$content->status] ?? ['bg-light text-dark', 'bi-question', $content->status]);
@endphp

<span class="badge {{ $class }}"
    @if ($scheduled) title="Goes live on {{ $content->published_at->format('Y-m-d H:i') }}" @endif>
    <i class="bi {{ $icon }}"></i> {{ $label }}
</span>
