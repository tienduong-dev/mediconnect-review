<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpecializationController extends Controller
{
    public function index(Request $req)
    {
        $query = Specialization::withCount('facilitySpecializations');

        if ($req->filled('keyword')) {
            $query->where('name', 'like', '%' . $req->keyword . '%');
        }

        if ($req->filled('status')) {
            $query->where('status', $req->status);
        }

        $specializations = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.specializations.index', compact('specializations'));
    }

    public function create()
    {
        return view('admin.specializations.form', [
            'specialization' => new Specialization(['status' => 'Active']),
        ]);
    }

    public function store(Request $req)
    {
        $data = $this->validated($req);
        $data['slug'] = $this->uniqueSlug($data['name']);

        Specialization::create($data);

        return redirect('Admin/Specializations')->with('success', 'Da them chuyen khoa moi.');
    }

    public function edit($id)
    {
        $specialization = Specialization::findOrFail($id);

        return view('admin.specializations.form', compact('specialization'));
    }

    public function update(Request $req, $id)
    {
        $specialization = Specialization::findOrFail($id);
        $data = $this->validated($req, $specialization->id);

        // Chi sinh lai slug khi ten thay doi
        if ($data['name'] !== $specialization->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $specialization->id);
        }

        $specialization->update($data);

        return redirect('Admin/Specializations')->with('success', 'Da cap nhat chuyen khoa.');
    }

    /**
     * facility_specializations.specialization_id dung ON DELETE RESTRICT.
     */
    public function destroy($id)
    {
        $specialization = Specialization::findOrFail($id);

        if ($specialization->facilitySpecializations()->exists()) {
            return back()->with('error', 'Khong the xoa: chuyen khoa nay dang duoc gan cho co so y te. Hay chuyen sang Inactive.');
        }

        $specialization->delete();

        return redirect('Admin/Specializations')->with('success', 'Da xoa chuyen khoa.');
    }

    public function toggleStatus($id)
    {
        $specialization = Specialization::findOrFail($id);
        $specialization->status = $specialization->status === 'Active' ? 'Inactive' : 'Active';
        $specialization->save();

        return back()->with('success', 'Da doi trang thai chuyen khoa "' . $specialization->name . '".');
    }

    private function validated(Request $req, $ignoreId = null): array
    {
        return $req->validate([
            'name'        => ['required', 'string', 'max:150', Rule::unique('specializations', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status'      => ['required', Rule::in(['Active', 'Inactive'])],
        ], [
            'name.required' => 'Ten chuyen khoa khong duoc de trong.',
            'name.unique'   => 'Chuyen khoa nay da ton tai.',
        ]);
    }

    private function uniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'specialization';
        $slug = $base;
        $i = 2;

        while (
            Specialization::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
