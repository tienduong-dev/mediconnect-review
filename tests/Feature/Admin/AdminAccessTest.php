<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;

/**
 * Who is allowed through the admin doors, and what the panel refuses to do
 * even when the person asking is a real admin.
 *
 * Broken access control is the most common serious web vulnerability, and
 * it is the one that clicking around never finds: every screen looks right
 * to the person who has the rights.
 */
class AdminAccessTest extends AdminTestCase
{
    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('Admin/AdminDashboard')->assertRedirect('login');
        $this->get('Admin/Patients')->assertRedirect('login');
        $this->get('Admin/Appointments')->assertRedirect('login');
    }

    public function test_a_patient_cannot_reach_the_admin_panel(): void
    {
        $patient = \App\Models\User::find($this->makeUser('Patient'));

        $this->actingAs($patient)->get('Admin/AdminDashboard')->assertRedirect('login');
        $this->actingAs($patient)->get('Admin/Patients')->assertRedirect('login');
    }

    public function test_a_doctor_cannot_reach_the_admin_panel(): void
    {
        $doctor = \App\Models\User::find($this->makeUser('Doctor'));

        $this->actingAs($doctor)->get('Admin/Appointments')->assertRedirect('login');
    }

    public function test_a_deactivated_admin_is_signed_out_on_the_next_request(): void
    {
        $admin = \App\Models\User::find($this->makeUser('Admin', ['account_status' => 'Inactive']));

        $this->actingAs($admin)
            ->get('Admin/AdminDashboard')
            ->assertRedirect('login');
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        // Otherwise a single misclick locks the last admin out of the system
        $this->actingAs($this->admin())
            ->post('Admin/User/ToggleAccountStatus/' . $this->adminId)
            ->assertSessionHas('error');

        $this->assertSame(
            'Active',
            DB::table('users')->where('id', $this->adminId)->value('account_status')
        );
    }

    public function test_deactivating_a_doctor_keeps_the_account_and_its_history(): void
    {
        $doctorUser = $this->makeUser('Doctor');

        $this->actingAs($this->admin())
            ->post('Admin/User/ToggleAccountStatus/' . $doctorUser)
            ->assertRedirect();

        // Soft deactivation, never a delete: medical history must survive
        $this->assertDatabaseHas('users', [
            'id' => $doctorUser,
            'account_status' => 'Inactive',
        ]);
    }

    public function test_the_doctor_profile_screen_refuses_a_patient_id(): void
    {
        // Without the user_type guard this would open the "add professional
        // profile" form for a patient and attach a doctors row to them.
        $patient = $this->makeUser('Patient');

        $this->actingAs($this->admin())
            ->get('Admin/DoctorProfile/' . $patient)
            ->assertNotFound();
    }
}
