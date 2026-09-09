@extends('admin/AdminLayout')

@section('page-title', $city->id ? 'Edit City' : 'New City')

@section('admin-content')
    @php($isEdit = (bool) $city->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">{{ $isEdit ? 'Edit City' : 'Add New City' }}</h5>
            </div>

            <form method="POST"
                action="{{ $isEdit ? url('Admin/Cities/Update', $city->id) : url('Admin/Cities/Store') }}">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">City name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $city->name) }}"
                            placeholder="e.g. Hanoi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" value="{{ old('state', $city->state) }}"
                            placeholder="Optional">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Country <span class="text-danger">*</span></label>
                        <input type="text" name="country" class="form-control"
                            value="{{ old('country', $city->country ?? 'Vietnam') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" @selected(old('status', $city->status) === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $city->status) === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Save changes' : 'Create' }}
                    </button>
                    <a href="{{ url('Admin/Cities') }}" class="btn btn-outline-secondary">Cancel</a>
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
                        Deleting cannot be undone. A city that still has facilities cannot be deleted.
                        Prefer setting the record to Inactive instead.
                    </div>
                    <form method="POST" action="{{ url('Admin/Cities/Delete', $city->id) }}"
                        onsubmit="return confirm('Permanently delete this city? This cannot be undone.')">
                        @csrf
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete city
                        </button>
                    </form>
                </div>
            </div>
        @endif

    </section>
@endsection
