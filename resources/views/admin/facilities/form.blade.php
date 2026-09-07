@extends('admin/AdminLayout')

@section('admin-content')
    @php($isEdit = (bool) $facility->id)

    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark">
                <h5 class="mb-0 text-white">{{ $isEdit ? 'Sửa cơ sở y tế' : 'Thêm cơ sở y tế mới' }}</h5>
            </div>

            <form method="POST"
                action="{{ $isEdit ? url('Admin/Facilities/Update', $facility->id) : url('Admin/Facilities/Store') }}">
                @csrf

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thành phố <span class="text-danger">*</span></label>
                            <select name="city_id" class="form-select" required>
                                <option value="">-- Chọn thành phố --</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}" @selected(old('city_id', $facility->city_id) == $city->id)>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mã cơ sở <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $facility->code) }}"
                                placeholder="VD: HNC" required>
                            <small class="text-muted">Mã viết tắt, không trùng với cơ sở khác.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tên cơ sở <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $facility->name) }}"
                            placeholder="VD: MediConnect Hanoi Central Hospital" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Địa chỉ khám <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control"
                            value="{{ old('address', $facility->address) }}" placeholder="VD: Ba Dinh, Hanoi" required>
                        <small class="text-muted">Đây là địa chỉ hiển thị cho bệnh nhân khi đặt lịch.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control"
                                value="{{ old('phone', $facility->phone) }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $facility->email) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $facility->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" @selected(old('status', $facility->status) === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $facility->status) === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">
                        <i class="bi bi-save"></i> {{ $isEdit ? 'Lưu thay đổi' : 'Thêm mới' }}
                    </button>
                    <a href="{{ url('Admin/Facilities') }}" class="btn btn-outline-secondary">Huỷ</a>
                </div>
            </form>
        </div>
    </section>
@endsection
