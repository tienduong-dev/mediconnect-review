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
     * Why each active assignment is unbookable, not just that it is.
     *
     * Three causes look identical on a booking form but need different
     * action, so they are counted separately:
     *
     *   never     - no slot row has ever existed; the doctor has not opened
     *               a schedule at all
     *   expired   - slots exist but every one of them is in the past, which
     *               is what a stale seed or an abandoned schedule looks like
     *   taken     - future slots exist but none is still Available
     */
    private function coverage(): array
    {
        $today = now()->toDateString();

        $stats = DB::table('doctor_appointment_slots')
            ->select('doctor_assignment_id')
            ->selectRaw('MAX(slot_date) as last_date')
            ->selectRaw('SUM(CASE WHEN slot_date >= ? THEN 1 ELSE 0 END) as future_total', [$today])
            ->selectRaw('SUM(CASE WHEN slot_date >= ? AND status = ? THEN 1 ELSE 0 END) as future_open', [$today, 'Available'])
            ->groupBy('doctor_assignment_id')
            ->get()
            ->keyBy('doctor_assignment_id');

        $active = DoctorAssignment::with([
                'doctor.user:id,name',
                'facilitySpecialization.facility:id,name',
                'facilitySpecialization.specialization:id,name',
            ])
            ->where('status', 'Active')
            ->get();

        $starved = [];
        $covered = 0;
        $reasons = ['never' => 0, 'expired' => 0, 'taken' => 0];

        foreach ($active as $assignment) {
            $row = $stats[$assignment->id] ?? null;

            if ($row && (int) $row->future_open > 0) {
                $covered++;
                continue;
            }

            if (!$row) {
                $reason = 'never';
            } elseif ((int) $row->future_total === 0) {
                $reason = 'expired';
            } else {
                $reason = 'taken';
            }

            $reasons[$reason]++;

            $starved[] = [
                'assignment' => $assignment,
                'reason'     => $reason,
                'last_date'  => $row->last_date ?? null,
            ];
        }

        return [
            'starved' => $starved,
            'covered' => $covered,
            'total'   => $active->count(),
            'reasons' => $reasons,
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
