<?php

namespace Tests\Feature\Admin;

/**
 * The global search box.
 *
 * The interesting case is the wildcard one: query bindings stop SQL
 * injection but they do not stop "%" from keeping its special meaning
 * inside a LIKE pattern, so a term of "%" would otherwise match every row
 * in five tables at once.
 */
class SearchTest extends AdminTestCase
{
    public function test_it_finds_a_patient_by_phone_number(): void
    {
        $patient = $this->makeUser('Patient', [
            'name' => 'Findable Patient',
            'number' => '0987654321',
        ]);

        $this->actingAs($this->admin())
            ->get('Admin/Search?q=0987654321')
            ->assertOk()
            ->assertSee('Findable Patient');
    }

    public function test_a_full_appointment_number_opens_that_appointment(): void
    {
        $assignment  = $this->makeAssignment();
        $slot        = $this->makeSlot($assignment, 'Held');
        $appointment = $this->makeAppointment($assignment, $slot, 'Pending');

        $number = \Illuminate\Support\Facades\DB::table('appointments')
            ->where('id', $appointment)
            ->value('appointment_number');

        $this->actingAs($this->admin())
            ->get('Admin/Search?q=' . $number)
            ->assertRedirect('Admin/Appointments/Show/' . $appointment);
    }

    public function test_a_lone_wildcard_does_not_match_everything(): void
    {
        $this->makeUser('Patient', ['name' => 'Should Not Appear']);

        $this->actingAs($this->admin())
            ->get('Admin/Search?q=' . urlencode('%%'))
            ->assertOk()
            ->assertDontSee('Should Not Appear');
    }

    public function test_a_single_character_is_rejected_as_too_short(): void
    {
        $this->actingAs($this->admin())
            ->get('Admin/Search?q=a')
            ->assertOk()
            ->assertSee('at least two characters', false);
    }
}
