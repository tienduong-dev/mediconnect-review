<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function index(Request $req)
    {
        $query = City::withCount('facilities');

        if ($req->filled('keyword')) {
            $query->where('name', 'like', '%' . $req->keyword . '%');
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }

        $cities = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.cities.index', compact('cities'));
    }

    public function create()
    {
        return view('admin.cities.form', ['city' => new City(['country' => 'Vietnam', 'status' => 'Active'])]);
    }

    public function store(Request $req)
    {
        City::create($this->validated($req));

        return redirect('Admin/Cities')->with('success', 'Da them thanh pho moi.');
    }

    public function edit($id)
    {
        $city = City::findOrFail($id);

        return view('admin.cities.form', compact('city'));
    }

    public function update(Request $req, $id)
    {
        $city = City::findOrFail($id);
        $city->update($this->validated($req, $city->id));

        return redirect('Admin/Cities')->with('success', 'Da cap nhat thanh pho.');
    }

    /**
     * facilities.city_id dung ON DELETE RESTRICT nen khong the xoa
     * thanh pho dang co co so y te. Truong hop do nen chuyen sang Inactive.
     */
    public function destroy($id)
    {
        $city = City::findOrFail($id);

        if ($city->facilities()->exists()) {
            return back()->with('error', 'Khong the xoa: thanh pho nay dang co co so y te. Hay chuyen sang Inactive.');
        }

        $city->delete();

        return redirect('Admin/Cities')->with('success', 'Da xoa thanh pho.');
    }

    public function toggleStatus($id)
    {
        $city = City::findOrFail($id);
        $city->status = $city->status === 'Active' ? 'Inactive' : 'Active';
        $city->save();

        return back()->with('success', 'Da doi trang thai thanh pho "' . $city->name . '".');
    }

    private function validated(Request $req, $ignoreId = null): array
    {
        return $req->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('cities')
                    ->where(fn($q) => $q->where('state', $req->state)->where('country', $req->country))
                    ->ignore($ignoreId),
            ],
            'state'   => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'status'  => ['required', Rule::in(['Active', 'Inactive'])],
        ], [
            'name.required'    => 'Ten thanh pho khong duoc de trong.',
            'name.unique'      => 'Thanh pho nay da ton tai.',
            'country.required' => 'Quoc gia khong duoc de trong.',
        ]);
    }
}
