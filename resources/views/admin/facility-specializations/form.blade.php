@extends('admin/AdminLayout')

@section('admin-content')
    @php($isEdit = (bool) $item->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">
                    {{ $isEdit ? 'Sửa phân công chuyên khoa' : 'Gán chuyên khoa cho cơ sở' }}
                </h5>
            </div>

            <form method="POST"
                action="{{ $isEdit ? url('Admin/FacilitySpecializations/Update', $item->id) : url('Admin/FacilitySpecializations/Store') }}">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Cơ sở y tế <span class="text-danger">*</span></label>
                        <select name="facility_id" class="form-select" required>
                            <option value="">-- Chọn cơ sở --</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(old('facility_id', $item->facility_id) == $f->id)>
                                    {{ $f->name }} ({{ $f->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chuyên khoa <span class="text-danger">*</span></label>
                        <select name="specialization_id" class="form-select" required>
                            <option value="">-- Chọn chuyên khoa --</option>
                            @foreach ($specializations as $sp)
                                <option value="{{ $sp->id }}" @selected(old('specialization_id', $item->specialization_id) == $sp->id)>
                                    {{ $sp->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Mỗi cơ sở chỉ được gán một chuyên khoa một lần.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" @selected(old('status', $item->status) === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $item->status) === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Gán chuyên khoa' }}
                    </button>
                    <a href="{{ url('Admin/FacilitySpecializations') }}" class="btn btn-outline-secondary">Huỷ</a>
                </div>
            </form>
        </div>
    </section>
@endsection
