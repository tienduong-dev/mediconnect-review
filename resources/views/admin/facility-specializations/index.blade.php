@extends('admin/AdminLayout')

@section('admin-content')
    <section class="container">

        @include('admin.partials.flash')

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Bảng này quyết định luồng tìm bác sĩ của bệnh nhân:
            <strong>Thành phố → Cơ sở → Chuyên khoa → Bác sĩ</strong>.
            Nếu một cơ sở chưa được gán chuyên khoa thì bệnh nhân sẽ không tìm thấy bác sĩ ở đó.
        </div>

        <div class="card shadow">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Chuyên khoa tại cơ sở</h5>
                <a href="{{ url('Admin/FacilitySpecializations/Create') }}" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> Gán chuyên khoa
                </a>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ url('Admin/FacilitySpecializations') }}" class="row g-2">
                    <div class="col-md-4">
                        <select name="facility_id" class="form-select">
                            <option value="">-- Tất cả cơ sở --</option>
                            @foreach ($facilities as $f)
                                <option value="{{ $f->id }}" @selected(request('facility_id') == $f->id)>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="specialization_id" class="form-select">
                            <option value="">-- Tất cả chuyên khoa --</option>
                            @foreach ($specializations as $sp)
                                <option value="{{ $sp->id }}" @selected(request('specialization_id') == $sp->id)>{{ $sp->name }}</option>
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
                        <a href="{{ url('Admin/FacilitySpecializations') }}" class="btn btn-outline-secondary">Xoá lọc</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-bordered align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width:1%">#</th>
                            <th>Cơ sở</th>
                            <th>Thành phố</th>
                            <th>Chuyên khoa</th>
                            <th class="text-center">Số bác sĩ</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center text-nowrap" style="width:1%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $item)
                            <tr>
                                <th>{{ $items->firstItem() + $index }}</th>
                                <td>{{ $item->facility->name ?? '—' }}</td>
                                <td>{{ $item->facility->city->name ?? '—' }}</td>
                                <td>{{ $item->specialization->name ?? '—' }}</td>
                                <td class="text-center">{{ $item->doctor_assignments_count }}</td>
                                <td class="text-center">
                                    <form method="POST"
                                        action="{{ url('Admin/FacilitySpecializations/ToggleStatus', $item->id) }}"
                                        class="d-inline">
                                        @csrf
                                        <button
                                            class="btn btn-sm {{ $item->status === 'Active' ? 'btn-success' : 'btn-secondary' }}"
                                            title="Bấm để đổi trạng thái">
                                            {{ $item->status }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ url('Admin/FacilitySpecializations/Edit', $item->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                        action="{{ url('Admin/FacilitySpecializations/Delete', $item->id) }}"
                                        class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn gỡ chuyên khoa này khỏi cơ sở?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Chưa có phân công chuyên khoa nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $items->links() }}
            </div>
        </div>
    </section>
@endsection
