<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Facility;
use App\Models\Specialization;
use App\Models\FacilitySpecialization;
use App\Models\DoctorAssignment;
use Illuminate\Http\Request;

class DoctorSearchController extends Controller
{
    // Search page
    public function index()
    {
        $cities = City::where('status', 'Active')
            ->orderBy('name')
            ->get();

        return view(
            'patient.PatientDoctorSearch',
            compact('cities')
        );
    }


    // Get facilities by city
    public function getFacilities($cityId)
    {
        $facilities = Facility::where('city_id', $cityId)
            ->where('status', 'Active')
            ->whereHas('facilitySpecializations', function ($query) {

                $query->where('status', 'Active')
                    ->whereHas('doctorAssignments', function ($assignment) {

                        $assignment->where('status', 'Active')
                            ->whereHas('doctor', function ($doctor) {

                                $doctor->where('status', 'Active');
                            });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($facilities);
    }


    // Get specializations available at selected facility
    public function getSpecializations($facilityId)
    {
        $specializations = Specialization::where('status', 'Active')
            ->whereHas(
                'facilitySpecializations',
                function ($query) use ($facilityId) {

                    $query->where('facility_id', $facilityId)
                        ->where('status', 'Active')
                        ->whereHas(
                            'doctorAssignments',
                            function ($assignment) {

                                $assignment
                                    ->where('status', 'Active')
                                    ->whereHas(
                                        'doctor',
                                        function ($doctor) {

                                            $doctor->where(
                                                'status',
                                                'Active'
                                            );
                                        }
                                    );
                            }
                        );
                }
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($specializations);
    }


    // Get doctors by facility + specialization
    public function getDoctors(Request $request)
    {
        $validated = $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'specialization_id' => 'required|exists:specializations,id',
        ]);

        $facilitySpecialization =
            FacilitySpecialization::where(
                'facility_id',
                $validated['facility_id']
            )
            ->where(
                'specialization_id',
                $validated['specialization_id']
            )
            ->where('status', 'Active')
            ->first();

        if (!$facilitySpecialization) {
            return response()->json([]);
        }

        $assignments = DoctorAssignment::with('doctor.user')
            ->where(
                'facility_specialization_id',
                $facilitySpecialization->id
            )
            ->where('status', 'Active')
            ->whereHas('doctor', function ($query) {
                $query->where('status', 'Active');
            })
            ->get();

        $doctors = $assignments->map(function ($assignment) {

            return [
                'assignment_id' => $assignment->id,
                'doctor_id' => $assignment->doctor->id,
                'user_id' => $assignment->doctor->user->id,
                'name' => $assignment->doctor->user->name,
                'expertise' => $assignment->doctor->expertise,
                'profession' => $assignment->doctor->profession,
                'experience' => $assignment->doctor->experience,
                'education' => $assignment->doctor->education,
            ];
        });

        return response()->json($doctors);
    }
}