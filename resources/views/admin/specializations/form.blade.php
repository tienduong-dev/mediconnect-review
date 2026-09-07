@extends('admin/AdminLayout')

@section('admin-content')
    @php($isEdit = (bool) $specialization->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">{{ $isEdit ? 'Sửa chuyên khoa' : 'Thêm chuyên khoa mới' }}</h5>
            </div>

            <form method="POST"
                action="{{ $isEdit ? url('Admin/Specializations/Update', $specialization->id) : url('Admin/Specializations/Store') }}">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tên chuyên khoa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                            value="{{ old('name', $specialization->name) }}" placeholder="VD: Cardiology" required>
                        <small class="text-muted">Slug (đường dẫn) sẽ được sinh tự động từ tên.</small>
                    </div>

                    @if ($isEdit)
                        <div class="mb-3">
                            <label class="form-label">Slug hiện tại</label>
                            <input type="text" class="form-control" value="{{ $specialization->slug }}" disabled>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Mô tả ngắn về chuyên khoa">{{ old('description', $specialization->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" @selected(old('status', $specialization->status) === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $specialization->status) === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Thêm mới' }}
                    </button>
                    <a href="{{ url('Admin/Specializations') }}" class="btn btn-outline-secondary">Huỷ</a>
                </div>
            </form>
        </div>
    </section>
@endsection
