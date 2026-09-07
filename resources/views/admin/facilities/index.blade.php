@extends('admin/AdminLayout')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Cơ sở y tế</h5>
                <a href="{{ url('Admin/Facilities/Create') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> Thêm cơ sở
                </a>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ url('Admin/Facilities') }}" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control"
                            placeholder="Tìm theo tên hoặc mã cơ sở...">
                    </div>
                    <div class="col-md-3">
                        <select name="city_id" class="form-select">
                            <option value="">-- Tất cả thành phố --</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected(request('city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">-- Trạng thái --</option>
                            <option value="Active" @selected(request('status') === 'Active')>Active</option>
                            <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary"><i class="bi bi-search"></i> Lọc</button>
                        <a href="{{ url('Admin/Facilities') }}" class="btn btn-outline-secondary">Xoá lọc</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width:1%">#</th>
                            <th>Mã</th>
                            <th>Tên cơ sở</th>
                            <th>Thành phố</th>
                            <th>Địa chỉ</th>
                            <th>Điện thoại</th>
                            <th class="text-center">Chuyên khoa</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center text-nowrap" style="width:1%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($facilities as $index => $f)
                            <tr>
                                <th>{{ $facilities->firstItem() + $index }}</th>
                                <td><span class="badge bg-secondary">{{ $f->code }}</span></td>
                                <td>{{ $f->name }}</td>
                                <td>{{ $f->city->name ?? '—' }}</td>
                                <td>{{ Str::limit($f->address, 40) }}</td>
                                <td>{{ $f->phone }}</td>
                                <td class="text-center">{{ $f->facility_specializations_count }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ url('Admin/Facilities/ToggleStatus', $f->id) }}"
                                        class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm {{ $f->status === 'Active' ? 'btn-success' : 'btn-secondary' }}"
                                            title="Bấm để đổi trạng thái">
                                            {{ $f->status }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/Facilities/Edit', $f->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ url('Admin/Facilities/Delete', $f->id) }}" class="d-inline"
                                        onsubmit="return confirm('Bạn chắc chắn muốn xoá cơ sở {{ $f->name }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Chưa có cơ sở y tế nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $facilities->links() }}
            </div>
        </div>
    </section>
@endsection
