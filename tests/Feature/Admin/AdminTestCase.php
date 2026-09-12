<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Shared fixture builder for the admin feature tests.
 *
 * The project's schema lives in a .sql file the whole team agreed on, not
 * in migrations, so RefreshDatabase has nothing to build from. These tests
 * therefore run against a real MySQL database that already has that schema
 * (appointment_db_test, configured in phpunit.xml) and wrap every test in a
 * transaction that is rolled back afterwards. Nothing a test writes
 * survives, and no test can see another test's rows.
 *
 * Rows are inserted through the query builder rather than factories,
 * because the project has no factories and the schema is the contract.
 */
abstract class AdminTestCase extends TestCase
{
    use DatabaseTransactions;

    protected int $adminId;
    protected int $cityId;
    protected int $facilityId;
    protected int $specializationId;
    protected int $facilitySpecializationId;

    protected function setUp(): void
    {
        parent::setUp();

        // Columns follow the team's .sql exactly: cities has state/country
        // and no slug, facilities require a unique code and a phone.
        $this->cityId = DB::table('cities')->insertGetId([
            'name' => 'Test City ' . $this->rand(),
            'state' => 'Test State',
            'country' => 'Vietnam',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->facilityId = DB::table('facilities')->insertGetId([
            'city_id' => $this->cityId,
            'name' => 'Test Clinic ' . $this->rand(),
            'code' => 'TC' . substr($this->rand(), 0, 8),
            'address' => '1 Test Street',
            'phone' => '0912345678',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->specializationId = DB::table('specializations')->insertGetId([
            'name' => 'Test Specialization ' . $this->rand(),
            'slug' => 'test-spec-' . $this->rand(),
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->facilitySpecializationId = DB::table('facility_specializations')->insertGetId([
            'facility_id' => $this->facilityId,
            'specialization_id' => $this->specializationId,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->adminId = $this->makeUser('Admin');
    }

    /** Unique-ish suffix so parallel rows never collide on a UNIQUE key. */
    protected function rand(): string
    {
        return substr(str_replace('.', '', uniqid('', true)), -10);
    }

    protected function makeUser(string $type, array $overrides = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => $type . ' ' . $this->rand(),
            'email' => strtolower($type) . $this->rand() . '@test.local',
            'number' => substr('9' . $this->rand(), 0, 10),
            // users_patient_address_check makes this mandatory for patients
            'address' => '1 Test Street',
            'password' => bcrypt('Password@123'),
            'user_type' => $type,
            'account_status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /** A doctor user plus its doctors row plus an active assignment. */
    protected function makeAssignment(): int
    {
        $userId = $this->makeUser('Doctor');

        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $userId,
            'image' => 'test.jpg',
            'expertise' => 'Testing',
            'experience' => 5,
            'education' => 'Test University',
            'profession' => 'Doctor',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('doctor_assignments')->insertGetId([
            'doctor_id' => $doctorId,
            'facility_specialization_id' => $this->facilitySpecializationId,
            'room_number' => 'R1',
            'consultation_fee' => 100000,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeSlot(int $assignmentId, string $status = 'Available', ?Carbon $date = null, string $start = '09:00:00'): int
    {
        $date ??= Carbon::today()->addDays(3);

        return DB::table('doctor_appointment_slots')->insertGetId([
            'doctor_assignment_id' => $assignmentId,
            'slot_date' => $date->toDateString(),
            'start_time' => $start,
            'end_time' => Carbon::parse($start)->addMinutes(30)->format('H:i:s'),
            'status' => $status,
            'held_at' => $status === 'Held' ? now() : null,
            'booked_at' => $status === 'Booked' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeAppointment(int $assignmentId, int $slotId, string $status = 'Pending', ?int $patientId = null): int
    {
        $slot = DB::table('doctor_appointment_slots')->find($slotId);

        return DB::table('appointments')->insertGetId([
            'appointment_number' => 'APT-' . now()->year . '-' . substr($this->rand(), 0, 6),
            'patient_user_id' => $patientId ?? $this->makeUser('Patient'),
            'facility_specialization_id' => $this->facilitySpecializationId,
            'doctor_assignment_id' => $assignmentId,
            'slot_id' => $slotId,
            'patient_name' => 'Test Patient',
            'patient_email' => 'patient' . $this->rand() . '@test.local',
            'patient_phone' => '0912345678',
            'appointment_date' => $slot->slot_date,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'examination_reason' => 'Routine check',
            'status' => $status,
            'booked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function admin(): \App\Models\User
    {
        return \App\Models\User::find($this->adminId);
    }

    protected function slotStatus(int $slotId): string
    {
        return DB::table('doctor_appointment_slots')->where('id', $slotId)->value('status');
    }

    protected function appointmentStatus(int $appointmentId): string
    {
        return DB::table('appointments')->where('id', $appointmentId)->value('status');
    }
}
