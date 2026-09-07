<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilitySpecialization;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Quan ly viec mot co so y te cung cap nhung chuyen khoa nao.
 * Day la bang ban le quyet dinh luong tim bac si cua Patient:
 * City -> Facility -> Specialization -> Doctor.
 */
class FacilitySpecializationController extends Controller
{
    public function index(Request $req)
    {
        $query = FacilitySpecialization::with(['facility.city', 'specialization'])
            ->withCount('doctorAssignments');

        if ($req->filled('facility_id')) {
            $query->where('facility_id', $req->facility_id);
        }

        if ($req->filled('specialization_id')) {
            $query->where('specialization_id', $req->specialization_id);
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }

        $items = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('admin.facility-specializations.index', [
            'items'           => $items,
            'facilities'      => Facility::orderBy('name')->get(),
            'specializations' => Specialization::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.facility-specializations.form', [
            'item'            => new FacilitySpecialization(['status' => 'Active']),
            'facilities'      => Facility::where('status', 'Active')->orderBy('name')->get(),
            'specializations' => Specialization::where('status', 'Active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $req)
    {
        FacilitySpecialization::create($this->validated($req));

        return redirect('Admin/FacilitySpecializations')->with('success', 'Da gan chuyen khoa cho co so.');
    }

    public function edit($id)
    {
        return view('admin.facility-specializations.form', [
            'item'            => FacilitySpecialization::findOrFail($id),
            'facilities'      => Facility::orderBy('name')->get(),
            'specializations' => Specialization::orderBy('name')->get(),
        ]);
    }

    public function update(Request $req, $id)
    {
        $item = FacilitySpecialization::findOrFail($id);
        $item->update($this->validated($req, $item->id));

        return redirect('Admin/FacilitySpecializations')->with('success', 'Da cap nhat phan cong chuyen khoa.');
    }

    /**
     * doctor_assignments.facility_specialization_id dung ON DELETE RESTRICT.
     */
    public function destroy($id)
    {
        $item = FacilitySpecialization::findOrFail($id);

        if ($item->doctorAssignments()->exists()) {
            return back()->with('error', 'Khong the xoa: con bac si dang duoc phan cong vao chuyen khoa nay tai co so. Hay chuyen sang Inactive.');
        }

        $item->delete();

        return redirect('Admin/FacilitySpecializations')->with('success', 'Da go chuyen khoa khoi co so.');
    }

    public function toggleStatus($id)
    {
        $item = FacilitySpecialization::findOrFail($id);
        $item->status = $item->status === 'Active' ? 'Inactive' : 'Active';
        $item->save();

        return back()->with('success', 'Da doi trang thai phan cong chuyen khoa.');
    }

    private function validated(Request $req, $ignoreId = null): array
    {
        return $req->validate([
            'facility_id'       => ['required', 'exists:facilities,id'],
            'specialization_id' => [
                'required',
                'exists:specializations,id',
                Rule::unique('facility_specializations')
                    ->where(fn($q) => $q->where('facility_id', $req->facility_id))
                    ->ignore($ignoreId),
            ],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ], [
            'facility_id.required'         => 'Vui long chon co so y te.',
            'specialization_id.required'   => 'Vui long chon chuyen khoa.',
            'specialization_id.unique'     => 'Co so nay da co chuyen khoa do roi.',
        ]);
    }
}
