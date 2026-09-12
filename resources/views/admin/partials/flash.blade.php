{{-- Flash messages and validation errors, shared by every admin screen. --}}

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-circle-fill mt-1"></i>
        <div>
            <strong>Please check the following:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
