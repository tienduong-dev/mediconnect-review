@extends('admin/AdminLayout')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Chuyên khoa</h5>
                <a href="{{ url('Admin/Specializations/Create') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> Thêm chuyên khoa
                </a>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ url('Admin/Specializations') }}" class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control"
                            placeholder="Tìm theo tên chuyên khoa...">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="Active" @selected(request('status') === 'Active')>Active</option>
                            <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary"><i class="bi bi-search"></i> Lọc</button>
                        <a href="{{ url('Admin/Specializations') }}" class="btn btn-outline-secondary">Xoá lọc</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width:1%">#</th>
                            <th>Tên chuyên khoa</th>
                            <th>Slug</th>
                            <th>Mô tả</th>
                            <th class="text-center">Số cơ sở áp dụng</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center text-nowrap" style="width:1%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($specializations as $index => $sp)
                            <tr>
                                <th>{{ $specializations->firstItem() + $index }}</th>
                                <td>{{ $sp->name }}</td>
                                <td><code>{{ $sp->slug }}</code></td>
                                <td>{{ Str::limit($sp->description, 60) ?: '—' }}</td>
                                <td class="text-center">{{ $sp->facility_specializations_count }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ url('Admin/Specializations/ToggleStatus', $sp->id) }}"
                                        class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm {{ $sp->status === 'Active' ? 'btn-success' : 'btn-secondary' }}"
                                            title="Bấm để đổi trạng thái">
                                            {{ $sp->status }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/Specializations/Edit', $sp->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ url('Admin/Specializations/Delete', $sp->id) }}"
                                        class="d-inline"
                                        onsubmit="return confirm('Bạn chắc chắn muốn xoá chuyên khoa {{ $sp->name }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Chưa có chuyên khoa nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $specializations->links() }}
            </div>
        </div>
    </section>
@endsection
