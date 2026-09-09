@extends('admin/AdminLayout')

@section('page-title', $content->id ? 'Edit Article' : 'New Article')

@section('admin-content')
    @php($isEdit = (bool) $content->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">{{ $isEdit ? 'Edit Article' : 'New Article' }}</h5>
            </div>

            <form method="POST" enctype="multipart/form-data"
                action="{{ $isEdit ? url('Admin/MedicalContents/Update', $content->id) : url('Admin/MedicalContents/Store') }}">
                @csrf

                <div class="card-body">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" maxlength="255"
                                value="{{ old('title', $content->title) }}" required>
                            @if ($isEdit)
                                <small class="text-muted">
                                    Public URL: <code>/medical-contents/{{ $content->slug }}</code> &mdash;
                                    it only changes if you change the title.
                                </small>
                            @endif
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="content_type" class="form-select" required>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('content_type', $content->content_type) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Summary</label>
                        <textarea name="summary" class="form-control" rows="2"
                            maxlength="500">{{ old('summary', $content->summary) }}</textarea>
                        <small class="text-muted">Shown on the article cards. Keep it under two sentences.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Body <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control" rows="14" required>{{ old('body', $content->body) }}</textarea>
                        <small class="text-muted">
                            Plain text. Line breaks are kept; HTML tags are escaped and will show as text.
                        </small>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Featured image</label>
                            <input type="file" name="featured_image" class="form-control"
                                accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">JPG, PNG or WEBP, up to 2 MB.</small>

                            @if ($content->featured_image)
                                <div class="mt-2 d-flex align-items-center gap-3">
                                    <img src="{{ asset('storage/' . $content->featured_image) }}" alt=""
                                        style="height:70px; border-radius:6px; object-fit:cover;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_image" value="1"
                                            id="removeImage">
                                        <label class="form-check-label small" for="removeImage">
                                            Remove current image
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Source URL</label>
                            <input type="url" name="source_url" class="form-control" maxlength="1000"
                                placeholder="https://..." value="{{ old('source_url', $content->source_url) }}">
                            <small class="text-muted">Where the information came from, if it is not original.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="statusSelect" class="form-select" required>
                                <option value="Draft" @selected(old('status', $content->status) === 'Draft')>Draft — not visible</option>
                                <option value="Published" @selected(old('status', $content->status) === 'Published')>Published — live on the site</option>
                                <option value="Archived" @selected(old('status', $content->status) === 'Archived')>Archived — hidden, kept</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3" id="publishDateWrap">
                            <label class="form-label">Publish at</label>
                            <input type="datetime-local" name="published_at" class="form-control"
                                value="{{ old('published_at', optional($content->published_at)->format('Y-m-d\TH:i')) }}">
                            <small class="text-muted">Leave empty to publish now, or set a future time to schedule.</small>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Save changes' : 'Create article' }}
                    </button>
                    <a href="{{ $isEdit ? url('Admin/MedicalContents/Show', $content->id) : url('Admin/MedicalContents') }}"
                        class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>

    <script>
        // The publish date is only meaningful for a Published article.
        (function () {
            var select = document.getElementById('statusSelect');
            var wrap = document.getElementById('publishDateWrap');

            function sync() {
                wrap.style.display = select.value === 'Published' ? '' : 'none';
            }

            select.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
