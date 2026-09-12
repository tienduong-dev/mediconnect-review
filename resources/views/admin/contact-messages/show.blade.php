@extends('admin/AdminLayout')

@section('page-title', 'Contact Message')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ $message->subject ?: 'No subject' }}</h5>
                        @include('admin.contact-messages.status-badge', ['status' => $message->status])
                    </div>

                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered m-0">
                            <tbody>
                                <tr>
                                    <th style="width:150px;">From</th>
                                    <td>
                                        {{ $message->name }}
                                        @if ($message->user)
                                            <span class="badge bg-light text-dark">registered account</span>
                                        @else
                                            <span class="small text-muted">(guest, not signed in)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><a href="tel:{{ $message->phone }}">{{ $message->phone }}</a></td>
                                </tr>
                                <tr>
                                    <th>Received</th>
                                    <td>{{ optional($message->created_at)->format('Y-m-d H:i') }}</td>
                                </tr>
                                @if ($message->resolved_at)
                                    <tr class="table-success">
                                        <th>Resolved</th>
                                        <td>
                                            {{ substr((string) $message->resolved_at, 0, 16) }}
                                            by {{ $message->resolvedBy->name ?? 'unknown' }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body border-top">
                        <h6 class="text-uppercase small fw-bold text-muted mb-2">Message</h6>
                        {{-- Escaped, line breaks preserved. --}}
                        <div style="white-space:pre-wrap; line-height:1.7;">{{ $message->message }}</div>
                    </div>
                </div>

                <div class="card shadow mt-4">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-white">Internal notes</h5>
                    </div>
                    <form method="POST" action="{{ url('Admin/ContactMessages/Notes', $message->id) }}">
                        @csrf
                        <div class="card-body">
                            <textarea name="admin_notes" class="form-control" rows="4" maxlength="2000"
                                placeholder="What was done about this? Only admins see this.">{{ old('admin_notes', $message->admin_notes) }}</textarea>
                            <small class="text-muted">Never sent to the sender.</small>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-save"></i> Save notes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-white">Actions</h5>
                    </div>
                    <div class="card-body d-grid gap-2">

                        <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . ($message->subject ?: 'Your enquiry')) }}"
                            class="btn btn-primary">
                            <i class="bi bi-reply"></i> Reply by email
                        </a>

                        @if ($message->status === 'New')
                            <form method="POST" action="{{ url('Admin/ContactMessages/Start', $message->id) }}">
                                @csrf
                                <button class="btn btn-outline-primary w-100">
                                    <i class="bi bi-hourglass-split"></i> Mark as In progress
                                </button>
                            </form>
                        @endif

                        @if ($message->status !== 'Resolved')
                            <form method="POST" action="{{ url('Admin/ContactMessages/Resolve', $message->id) }}">
                                @csrf
                                <label class="form-label small text-muted mb-1">Closing note (optional)</label>
                                <textarea name="admin_notes" class="form-control mb-2" rows="2" maxlength="2000"
                                    placeholder="How was it handled?"></textarea>
                                <button class="btn btn-success w-100">
                                    <i class="bi bi-check2-circle"></i> Resolve
                                </button>
                            </form>
                        @endif

                        @if ($message->status !== 'Spam')
                            <form method="POST" action="{{ url('Admin/ContactMessages/Spam', $message->id) }}"
                                onsubmit="return confirm('Mark this message as spam?')">
                                @csrf
                                <button class="btn btn-outline-dark w-100">
                                    <i class="bi bi-slash-circle"></i> Mark as spam
                                </button>
                            </form>
                        @endif

                        @if ($message->status !== 'New')
                            <form method="POST" action="{{ url('Admin/ContactMessages/Reopen', $message->id) }}">
                                @csrf
                                <button class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reopen
                                </button>
                            </form>
                        @endif

                        <a href="{{ url('Admin/ContactMessages') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to inbox
                        </a>
                    </div>
                </div>

                {{-- A real enquiry is never deleted, only spam is. --}}
                @if ($message->status === 'Spam')
                    <div class="card shadow mt-4 border-danger-subtle">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-danger fw-bold">Danger zone</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Deleting removes this message for good. Only spam can be deleted.
                            </p>
                            <form method="POST" action="{{ url('Admin/ContactMessages/Delete', $message->id) }}"
                                onsubmit="return confirm('Delete this spam message permanently?')">
                                @csrf
                                <button class="btn btn-outline-danger w-100">
                                    <i class="bi bi-trash"></i> Delete permanently
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
