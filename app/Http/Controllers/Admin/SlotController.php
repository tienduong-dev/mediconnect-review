<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorAppointmentSlot;
use App\Models\DoctorAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only window on doctor availability.
 *
 * The dashboard can already say "no available slot exists, so patients
 * cannot book", and the Adjust screen needs open slots to move an
 * appointment to. Until now there was no way to see where those slots come
 * from, or why a given day has none.
 *
 * Opening hours and slot generation belong to the Doctor module, so nothing
 * here writes: this answers "what does availability look like right now",
 * not "change it".
 */
class SlotController extends Controller
{
    public const STATUSES = ['Available', 'Held', 'Booked', 'Blocked'];

    /** Days shown on the grid at once. */
    private const WINDOW_DAYS = 7;

    public function index(Request $req)
    {
        $assignments = DoctorAssignment::with([
                'doctor.user:id,name',
                'facilitySpecialization.facility:id,name',
                'facilitySpecialization.specialization:id,name',
            ])
            ->where('status', 'Active')
            ->get()
            ->sortBy(fn ($a) => $a->doctor->user->name ?? '')
            ->values();

        // Default to the assignment the admin is most likely to be chasing:
        // the first one, unless a specific one was asked for.
        $assignmentId = (int) $req->input('assignment_id', 0);
        $selected = $assignments->firstWhere('id', $assignmentId) ?? $assignments->first();

        $from = $this->parseDate($req->input('from')) ?? Carbon::today();
        $days = collect(range(0, self::WINDOW_DAYS - 1))
            ->map(fn ($i) => $from->copy()->addDays($i));

        $slots = collect();
        $schedules = collect();

        if ($selected) {
            $slots = DoctorAppointmentSlot::where('doctor_assignment_id', $selected->id)
                ->whereBetween('slot_date', [$from->toDateString(), $days->last()->toDateString()])
                ->orderBy('slot_date')
                ->orderBy('start_time')
                ->get()
                ->groupBy(fn ($s) => $s->slot_date->toDateString());

            $schedules = $selected->schedules()
                ->orderByRaw("FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
                ->orderBy('start_time')
                ->get();
        }

        return view('admin.slots.index', [
            'assignments' => $assignments,
            'selected'    => $selected,
            'from'        => $from,
            'days'        => $days,
            'slots'       => $slots,
            'schedules'   => $schedules,
            'totals'      => $this->totals($selected?->id, $from, $days->last()),
            'coverage'    => $this->coverage(),
        ]);
    }

    // ------------------------------------------------------------------

    /** Slot counts per status inside the visible window. */
    private function totals(?int $assignmentId, Carbon $from, Carbon $to): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        if (!$assignmentId) {
            return $counts;
        }

        $rows = DoctorAppointmentSlot::where('doctor_assignment_id', $assignmentId)
            ->whereBetween('slot_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return array_merge($counts, $rows);
    }

    /**
     * Which active assignments have no open slot from today onwards.
     *
     * This is the list that explains a failed patient search, so it is
     * shown whichever doctor is currently selected.
     */
    private function coverage(): array
    {
        $withOpenSlots = DB::table('doctor_appointment_slots')
            ->where('status', 'Available')
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->distinct()
            ->pluck('doctor_assignment_id')
            ->all();

        $starved = DoctorAssignment::with([
                'doctor.user:id,name',
                'facilitySpecialization.facility:id,name',
                'facilitySpecialization.specialization:id,name',
            ])
            ->where('status', 'Active')
            ->when($withOpenSlots, fn ($q) => $q->whereNotIn('id', $withOpenSlots))
            ->get();

        return [
            'starved' => $starved,
            'covered' => count($withOpenSlots),
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable $e) {
            // A hand-edited query string must not take the page down
            return null;
        }
    }
}
