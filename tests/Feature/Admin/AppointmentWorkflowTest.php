<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The admin half of the appointment lifecycle.
 *
 * Patient books  -> Pending
 * Admin          -> Confirmed / Adjusted / Rejected
 * Doctor         -> Completed / NoShow
 *
 * These tests cover the middle row, and in particular the two things that
 * are easy to get wrong and impossible to spot by clicking around: the slot
 * status has to move in step with the appointment status, and a slot must
 * never end up booked twice.
 */
class AppointmentWorkflowTest extends AdminTestCase
{
    public function test_confirming_a_pending_appointment_books_its_slot(): void
    {
        Notification::fake();

        $assignment  = $this->makeAssignment();
        $slot        = $this->makeSlot($assignment, 'Held');
        $appointment = $this->makeAppointment($assignment, $slot, 'Pending');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Confirm/' . $appointment)
            ->assertRedirect();

        $this->assertSame('Confirmed', $this->appointmentStatus($appointment));
        $this->assertSame('Booked', $this->slotStatus($slot));

        $row = DB::table('appointments')->find($appointment);
        $this->assertNotNull($row->confirmed_at, 'confirmed_at must be stamped');

        $this->assertDatabaseHas('appointment_histories', [
            'appointment_id' => $appointment,
            'action' => 'Confirmed',
            'old_status' => 'Pending',
            'new_status' => 'Confirmed',
            'changed_by_user_id' => $this->adminId,
        ]);
    }

    public function test_an_already_confirmed_appointment_cannot_be_confirmed_again(): void
    {
        $assignment  = $this->makeAssignment();
        $slot        = $this->makeSlot($assignment, 'Booked');
        $appointment = $this->makeAppointment($assignment, $slot, 'Confirmed');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Confirm/' . $appointment)
            ->assertSessionHas('error');

        $this->assertSame('Confirmed', $this->appointmentStatus($appointment));
    }

    public function test_a_completed_appointment_cannot_be_dragged_back_to_confirmed(): void
    {
        $assignment  = $this->makeAssignment();
        $slot        = $this->makeSlot($assignment, 'Booked');
        $appointment = $this->makeAppointment($assignment, $slot, 'Completed');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Confirm/' . $appointment)
            ->assertSessionHas('error');

        $this->assertSame('Completed', $this->appointmentStatus($appointment));
    }

    public function test_rejecting_releases_the_slot_for_somebody_else(): void
    {
        Notification::fake();

        $assignment  = $this->makeAssignment();
        $slot        = $this->makeSlot($assignment, 'Held');
        $appointment = $this->makeAppointment($assignment, $slot, 'Pending');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Reject/' . $appointment, [
                'rejection_reason' => 'The doctor is on leave that day.',
            ])
            ->assertRedirect();

        $this->assertSame('Rejected', $this->appointmentStatus($appointment));

        // The whole point: the time becomes bookable again
        $this->assertSame('Available', $this->slotStatus($slot));

        $row = DB::table('appointments')->find($appointment);
        $this->assertSame('The doctor is on leave that day.', $row->rejection_reason);
    }

    public function test_rejecting_without_a_reason_is_refused(): void
    {
        $assignment  = $this->makeAssignment();
        $slot        = $this->makeSlot($assignment, 'Held');
        $appointment = $this->makeAppointment($assignment, $slot, 'Pending');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Reject/' . $appointment, ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('Pending', $this->appointmentStatus($appointment));
        $this->assertSame('Held', $this->slotStatus($slot));
    }

    public function test_adjusting_frees_the_old_slot_and_takes_the_new_one(): void
    {
        Notification::fake();

        $assignment  = $this->makeAssignment();
        $oldSlot     = $this->makeSlot($assignment, 'Booked', null, '09:00:00');
        $newSlot     = $this->makeSlot($assignment, 'Available', null, '10:00:00');
        $appointment = $this->makeAppointment($assignment, $oldSlot, 'Confirmed');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Adjust/' . $appointment, [
                'slot_id' => $newSlot,
                'note' => 'Patient asked for a later time.',
            ])
            ->assertRedirect();

        $this->assertSame('Available', $this->slotStatus($oldSlot), 'the old slot must be released');
        $this->assertSame('Booked', $this->slotStatus($newSlot), 'the new slot must be taken');

        $row = DB::table('appointments')->find($appointment);
        $this->assertSame($newSlot, (int) $row->slot_id);
        $this->assertSame('10:00:00', $row->start_time);

        $this->assertDatabaseHas('appointment_histories', [
            'appointment_id' => $appointment,
            'action' => 'Adjusted',
            'old_slot_id' => $oldSlot,
            'new_slot_id' => $newSlot,
        ]);
    }

    public function test_a_slot_that_is_already_booked_cannot_be_taken_again(): void
    {
        Notification::fake();

        $assignment  = $this->makeAssignment();
        $oldSlot     = $this->makeSlot($assignment, 'Booked', null, '09:00:00');
        $takenSlot   = $this->makeSlot($assignment, 'Booked', null, '11:00:00');
        $appointment = $this->makeAppointment($assignment, $oldSlot, 'Confirmed');

        $this->actingAs($this->admin())
            ->post('Admin/Appointments/Adjust/' . $appointment, ['slot_id' => $takenSlot])
            ->assertSessionHasErrors();

        // Nothing moved: the original booking is intact
        $row = DB::table('appointments')->find($appointment);
        $this->assertSame($oldSlot, (int) $row->slot_id);
        $this->assertSame('Booked', $this->slotStatus($oldSlot));
    }

    public function test_the_appointment_list_ignores_a_sort_column_that_is_not_whitelisted(): void
    {
        // A column name arriving from the query string must never reach
        // orderBy() unchecked; if it did, this would be an injection point.
        $this->actingAs($this->admin())
            ->get('Admin/Appointments?sort=' . urlencode('(SELECT 1)') . '&dir=asc')
            ->assertOk();
    }
}
