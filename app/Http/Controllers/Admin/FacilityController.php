<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    public function index(Request $req)
    {
        $query = Facility::with('city')->withCount('facilitySpecializations');

        if ($req->filled('keyword')) {
            $query->where(function ($q) use ($req) {
                $q->where('name', 'like', '%' . $req->keyword . '%')
                    ->orWhere('code', 'like', '%' . $req->keyword . '%');
            });
        }

        if ($req->filled('city_id')) {
            $query->where('city_id', $req->city_id);
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }

        $facilities = $query->orderBy('name')->paginate(10)->withQueryString();
        $cities = City::orderBy('name')->get();

        return view('admin.facilities.index', compact('facilities', 'cities'));
    }

    public function create()
    {
        return view('admin.facilities.form', [
            'facility' => new Facility(['status' => 'Active']),
            'cities'   => City::where('status', 'Active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $req)
    {
        Facility::create($this->validated($req));

        return redirect('Admin/Facilities')->with('success', 'Da them co so y te moi.');
    }

    public function edit($id)
    {
        return view('admin.facilities.form', [
            'facility' => Facility::findOrFail($id),
            'cities'   => City::orderBy('name')->get(),
        ]);
    }

    public function update(Request $req, $id)
    {
        $facility = Facility::findOrFail($id);
        $facility->update($this->validated($req, $facility->id));

        return redirect('Admin/Facilities')->with('success', 'Da cap nhat co so y te.');
    }

    /**
     * facility_specializations se bi CASCADE khi xoa facility, nhung
     * doctor_assignments.facility_specialization_id lai la RESTRICT
     * => chan lai neu con bac si dang duoc phan cong o day.
     */
    public function destroy($id)
    {
        $facility = Facility::withCount('facilitySpecializations')->findOrFail($id);

        $hasAssignment = $facility->facilitySpecializations()
            ->whereHas('doctorAssignments')
            ->exists();

        if ($hasAssignment) {
            return back()->with('error', 'Khong the xoa: con bac si dang duoc phan cong tai co so nay. Hay chuyen sang Inactive.');
        }

        $facility->delete();

        return redirect('Admin/Facilities')->with('success', 'Da xoa co so y te.');
    }

    public function toggleStatus($id)
    {
        $facility = Facility::findOrFail($id);
        $facility->status = $facility->status === 'Active' ? 'Inactive' : 'Active';
        $facility->save();

        return back()->with('success', 'Da doi trang thai co so "' . $facility->name . '".');
    }

    private function validated(Request $req, $ignoreId = null): array
    {
        return $req->validate([
            'city_id' => ['required', 'exists:cities,id'],
            'name'    => [
                'required',
                'string',
                'max:255',
                Rule::unique('facilities')->where(fn($q) => $q->where('city_id', $req->city_id))->ignore($ignoreId),
            ],
            'code'        => ['required', 'string', 'max:30', Rule::unique('facilities', 'code')->ignore($ignoreId)],
            'address'     => ['required', 'string', 'max:500'],
            'phone'       => ['required', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status'      => ['required', Rule::in(['Active', 'Inactive'])],
        ], [
            'city_id.required' => 'Vui long chon thanh pho.',
            'name.required'    => 'Ten co so khong duoc de trong.',
            'name.unique'      => 'Thanh pho nay da co co so cung ten.',
            'code.required'    => 'Ma co so khong duoc de trong.',
            'code.unique'      => 'Ma co so nay da duoc su dung.',
            'address.required' => 'Dia chi khong duoc de trong.',
            'phone.required'   => 'So dien thoai khong duoc de trong.',
        ]);
    }
}
