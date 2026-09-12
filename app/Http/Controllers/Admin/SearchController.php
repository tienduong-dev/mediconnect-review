<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\ContactMessage;
use App\Models\MedicalContent;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * One search box for the whole admin panel.
 *
 * A patient rings up and reads out a phone number. Without this, the admin
 * has to guess whether that person is in Patients or only on an appointment
 * booked for a relative, then search each module separately. This looks in
 * every place a person or a case can appear and groups what it finds.
 *
 * Nothing here writes, so the whole controller is read only by design.
 */
class SearchController extends Controller
{
    /** Rows shown per group before the "see all" link takes over. */
    private const PER_GROUP = 5;

    private const MIN_LENGTH = 2;

    public function index(Request $req)
    {
        $term = trim((string) $req->input('q', ''));

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return view('admin.search.index', [
                'term'    => $term,
                'groups'  => [],
                'total'   => 0,
                'tooShort' => $term !== '',
            ]);
        }

        $like = '%' . $this->escapeLike($term) . '%';

        // An appointment number is unique and unmistakable, so typing one
        // opens that appointment instead of showing a list of one result.
        if (preg_match('/^APT-\d{4}-\d{6}$/i', $term)) {
            $exact = Appointments::where('appointment_number', $term)->first();

            if ($exact) {
                return redirect('Admin/Appointments/Show/' . $exact->id);
            }
        }

        $groups = [
            'patients' => [
                'label' => 'Patients',
                'icon'  => 'bi-people',
                'tone'  => 'info',
                'all'   => 'Admin/Patients?keyword=' . urlencode($term),
                'rows'  => $this->people('Patient', $like),
            ],
            'doctors' => [
                'label' => 'Doctors',
                'icon'  => 'bi-person-badge',
                'tone'  => 'ok',
                'all'   => 'Admin/Doctors?keyword=' . urlencode($term),
                'rows'  => $this->people('Doctor', $like),
            ],
            'appointments' => [
                'label' => 'Appointments',
                'icon'  => 'bi-calendar2-check',
                'tone'  => 'brand',
                'all'   => 'Admin/Appointments?keyword=' . urlencode($term),
                'rows'  => $this->appointments($like),
            ],
            'messages' => [
                'label' => 'Contact messages',
                'icon'  => 'bi-envelope',
                'tone'  => 'warn',
                'all'   => 'Admin/ContactMessages?keyword=' . urlencode($term),
                'rows'  => $this->messages($like),
            ],
            'contents' => [
                'label' => 'Medical contents',
                'icon'  => 'bi-journal-medical',
                'tone'  => 'mute',
                'all'   => 'Admin/MedicalContents?keyword=' . urlencode($term),
                'rows'  => $this->contents($like),
            ],
        ];

        $groups = array_filter($groups, fn ($g) => count($g['rows']) > 0);
        $total  = array_sum(array_map(fn ($g) => count($g['rows']), $groups));

        return view('admin.search.index', compact('term', 'groups', 'total') + ['tooShort' => false]);
    }

    // ------------------------------------------------------------------

    private function people(string $type, string $like): array
    {
        return User::where('user_type', $type)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('number', 'like', $like);
            })
            ->orderBy('name')
            ->limit(self::PER_GROUP)
            ->get(['id', 'name', 'email', 'number', 'account_status'])
            ->map(fn ($u) => [
                'title'  => $u->name,
                'meta'   => $u->email . ' · ' . $u->number,
                'badge'  => $u->account_status,
                'tone'   => $u->account_status === 'Active' ? 'bg-success' : 'bg-secondary',
                'url'    => $type === 'Patient'
                    ? 'Admin/Patients/Show/' . $u->id
                    : 'Admin/DoctorProfile/' . $u->id,
            ])
            ->all();
    }

    private function appointments(string $like): array
    {
        return Appointments::where(function ($q) use ($like) {
                $q->where('appointment_number', 'like', $like)
                    ->orWhere('patient_name', 'like', $like)
                    ->orWhere('patient_phone', 'like', $like)
                    ->orWhere('patient_email', 'like', $like);
            })
            ->orderByDesc('appointment_date')
            ->limit(self::PER_GROUP)
            ->get(['id', 'appointment_number', 'patient_name', 'appointment_date', 'start_time', 'status'])
            ->map(fn ($a) => [
                'title' => $a->patient_name,
                'meta'  => $a->appointment_number . ' · '
                    . optional($a->appointment_date)->format('Y-m-d') . ' '
                    . substr((string) $a->start_time, 0, 5),
                'badge' => $a->status,
                'tone'  => match ($a->status) {
                    'Pending'   => 'bg-warning',
                    'Confirmed' => 'bg-primary',
                    'Completed' => 'bg-success',
                    'Rejected', 'Cancelled' => 'bg-danger',
                    default     => 'bg-secondary',
                },
                'url'   => 'Admin/Appointments/Show/' . $a->id,
            ])
            ->all();
    }

    private function messages(string $like): array
    {
        return ContactMessage::where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('subject', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->limit(self::PER_GROUP)
            ->get(['id', 'name', 'email', 'subject', 'status'])
            ->map(fn ($m) => [
                'title' => $m->subject ?: 'No subject',
                'meta'  => $m->name . ' · ' . $m->email,
                'badge' => $m->status,
                'tone'  => $m->status === 'New' ? 'bg-primary' : 'bg-secondary',
                'url'   => 'Admin/ContactMessages/Show/' . $m->id,
            ])
            ->all();
    }

    private function contents(string $like): array
    {
        return MedicalContent::where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('summary', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->limit(self::PER_GROUP)
            ->get(['id', 'title', 'content_type', 'status'])
            ->map(fn ($c) => [
                'title' => $c->title,
                'meta'  => $c->content_type,
                'badge' => $c->status,
                'tone'  => $c->status === 'Published' ? 'bg-success' : 'bg-secondary',
                'url'   => 'Admin/MedicalContents/Show/' . $c->id,
            ])
            ->all();
    }

    /**
     * Neutralise LIKE wildcards inside the search term.
     *
     * Bindings stop SQL injection, but they do not stop "%" from keeping
     * its meaning inside a LIKE pattern: a single "%" would otherwise match
     * every row in every table, and "_" would match any character. Both are
     * escaped so the term is treated as plain text.
     */
    private function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
