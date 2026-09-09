@extends('admin/AdminLayout')

@section('page-title', $item->id ? 'Edit Facility Specialization' : 'Assign Specialization')

@section('admin-content')
    @php($isEdit = (bool) $item->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">
                    {{ $isEdit ? 'Edit Facility Specialization' : 'Assign Specialization to Facility' }}
                </h5>
            </div>

            <form method="POST"
                action="{{ $isEdit ? url('Admin/FacilitySpecializations/Update', $item->id) : url('Admin/FacilitySpecializations/Store') }}">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Facilities <span class="text-danger">*</span></label>
                        <select name="facility_id" class="form-select" required>
                            <option value="">-- Select a facility --</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $item->facility_id) == $f->id)>
                                    {{ $f->name }} ({{ $f->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Specialization <span class="text-danger">*</span></label>
                        <select name="specialization_id" class="form-select" required>
                            <option value="">-- Select a specialization --</option>
                            @foreach ($specializations as $sp)
                                <option value="{{ $sp->id }}" @selected(old('specialization_id', $item->specialization_id) == $sp->id)>
                                    {{ $sp->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Each specialization can be assigned to a facility only once.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" @selected(old('status', $item->status) === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $item->status) === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Save changes' : 'Assign Specialization' }}
                    </button>
                    <a href="{{ url('Admin/FacilitySpecializations') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
        @if ($isEdit)
            <div class="card shadow mt-4 border-danger-subtle">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 text-danger fw-bold">Danger zone</h6>
                </div>
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="small text-muted mb-0">
                        Deleting cannot be undone. A mapping with doctors assigned to it cannot be deleted.
                        Prefer setting the record to Inactive instead.
                    </div>
                    <form method="POST" action="{{ url('Admin/FacilitySpecializations/Delete', $item->id) }}"
                        onsubmit="return confirm('Permanently delete this mapping? This cannot be undone.')">
                        @csrf
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete mapping
                        </button>
                    </form>
                </div>
            </div>
        @endif

    </section>
@endsection
