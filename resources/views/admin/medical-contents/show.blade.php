@extends('admin/AdminLayout')

@section('page-title', 'Review Article')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ $types[$content->content_type] ?? $content->content_type }}</h5>
                        @include('admin.medical-contents.status-badge', ['content' => $content])
                    </div>

                    <div class="card-body">
                        @if ($content->featured_image)
                            <img src="{{ asset('storage/' . $content->featured_image) }}" alt=""
                                class="w-100 mb-4" style="max-height:320px; object-fit:cover; border-radius:8px;">
                        @endif

                        <h4 class="fw-bold">{{ $content->title }}</h4>

                        @if ($content->summary)
                            <p class="text-muted">{{ $content->summary }}</p>
                        @endif

                        <hr>

                        {{-- Same rendering as the public page: escaped, line breaks kept. --}}
                        <div style="white-space:pre-wrap; line-height:1.7;">{{ $content->body }}</div>

                        @if ($content->source_url)
                            <hr>
                            <div class="small text-muted">
                                Source:
                                <a href="{{ $content->source_url }}" target="_blank" rel="noopener noreferrer">
                                    {{ \Illuminate\Support\Str::limit($content->source_url, 80) }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-white">Details</h5>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered m-0">
                            <tbody>
                                <tr><th style="width:120px;">Author</th><td>{{ $content->author->name ?? '—' }}</td></tr>
                                <tr><th>Slug</th><td class="small"><code>{{ $content->slug }}</code></td></tr>
                                <tr>
                                    <th>Created</th>
                                    <td>{{ optional($content->created_at)->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Published</th>
                                    <td>{{ optional($content->published_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow mt-4">
                    <div class="card-header bg-dark">
                        <h5 class="mb-0 text-white">Actions</h5>
                    </div>
                    <div class="card-body d-grid gap-2">

                        <a href="{{ url('Admin/MedicalContents/Edit', $content->id) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit article
                        </a>

                        @if ($content->status !== 'Published')
                            <form method="POST" action="{{ url('Admin/MedicalContents/Publish', $content->id) }}"
                                onsubmit="return confirm('Publish this article? It becomes visible to every visitor.')">
                                @csrf
                                <label class="form-label small text-muted mb-1">Publish at (empty = now)</label>
                                <input type="datetime-local" name="published_at" class="form-control mb-2">
                                <button class="btn btn-success w-100">
                                    <i class="bi bi-globe"></i> Publish
                                </button>
                            </form>
                        @endif

                        @if ($content->status === 'Published')
                            <form method="POST" action="{{ url('Admin/MedicalContents/Archive', $content->id) }}"
                                onsubmit="return confirm('Archive this article? Visitors will no longer see it, but nothing is deleted.')">
                                @csrf
                                <button class="btn btn-outline-dark w-100">
                                    <i class="bi bi-archive"></i> Archive
                                </button>
                            </form>
                        @endif

                        @if ($content->status === 'Archived')
                            <form method="POST" action="{{ url('Admin/MedicalContents/Restore', $content->id) }}">
                                @csrf
                                <button class="btn btn-outline-primary w-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> Move back to Draft
                                </button>
                            </form>
                        @endif

                        <a href="{{ url('Admin/MedicalContents') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to list
                        </a>
                    </div>
                </div>

                {{-- Deletion is only offered once the article is off the public site. --}}
                @if ($content->status !== 'Published')
                    <div class="card shadow mt-4 border-danger-subtle">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-danger fw-bold">Danger zone</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Deleting removes the article and its image for good. This cannot be undone.
                            </p>
                            <form method="POST" action="{{ url('Admin/MedicalContents/Delete', $content->id) }}"
                                onsubmit="return confirm('Delete &quot;{{ $content->title }}&quot; permanently? This cannot be undone.')">
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
