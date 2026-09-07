@extends('admin/AdminLayout')

@section('admin-content')
    @php($isEdit = (bool) $city->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">{{ $isEdit ? 'Sửa thành phố' : 'Thêm thành phố mới' }}</h5>
            </div>

            <form method="POST"
                action="{{ $isEdit ? url('Admin/Cities/Update', $city->id) : url('Admin/Cities/Store') }}">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tên thành phố <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $city->name) }}"
                            placeholder="VD: Hanoi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tỉnh / Bang</label>
                        <input type="text" name="state" class="form-control" value="{{ old('state', $city->state) }}"
                            placeholder="Có thể để trống">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quốc gia <span class="text-danger">*</span></label>
                        <input type="text" name="country" class="form-control"
                            value="{{ old('country', $city->country ?? 'Vietnam') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" @selected(old('status', $city->status) === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $city->status) === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Thêm mới' }}
                    </button>
                    <a href="{{ url('Admin/Cities') }}" class="btn btn-outline-secondary">Huỷ</a>
                </div>
            </form>
        </div>
    </section>
@endsection
