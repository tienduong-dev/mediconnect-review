<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Admin\Concerns\SortsListings;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

/**
 * Inbox for messages sent from the public contact form.
 *
 * New -> InProgress -> Resolved is the normal path; Spam is the side exit
 * for junk. Resolving stamps resolved_by_user_id and resolved_at, so the
 * question "who answered this, and when" always has an answer.
 *
 * Only a message parked in Spam can be deleted for good. A real enquiry is
 * never removed by a single click, because the sender may come back and
 * ask what happened to it.
 */
class ContactMessageController extends Controller
{
    use SortsListings;
    use LogsAdminActions;

    /** Mirrors the status ENUM in the contact_messages table. */
    public const STATUSES = ['New', 'InProgress', 'Resolved', 'Spam'];

    public function index(Request $req)
    {
        $query = ContactMessage::with(['user:id,name', 'resolvedBy:id,name']);

        if ($req->filled('keyword')) {
            $keyword = trim($req->keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('subject', 'like', '%' . $keyword . '%')
                    ->orWhere('message', 'like', '%' . $keyword . '%');
            });
        }

        if (in_array($req->input('status'), self::STATUSES, true)) {
            $query->where('status', $req->input('status'));
        }

        if ($req->filled('date_from')) {
            $query->whereDate('created_at', '>=', $req->input('date_from'));
        }

        if ($req->filled('date_to')) {
            $query->whereDate('created_at', '<=', $req->input('date_to'));
        }

        // Newest first by default: an inbox is read from the top
        $this->applySort(
            $query,
            $req,
            ['name', 'email', 'status', 'created_at'],
            'created_at',
            'desc'
        );

        $messages = $query->paginate($this->perPage($req))->withQueryString();

        return view('admin.contact-messages.index', [
            'messages' => $messages,
            'statuses' => self::STATUSES,
            'counts'   => $this->statusCounts(),
        ]);
    }

    public function show($id)
    {
        $message = ContactMessage::with(['user:id,name,email', 'resolvedBy:id,name'])
            ->findOrFail($id);

        return view('admin.contact-messages.show', compact('message'));
    }

    /** New -> InProgress, so the rest of the team sees it is being handled. */
    public function start($id)
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->status !== 'New') {
            return back()->with('error', 'Only a new message can be moved to In progress.');
        }

        $this->transition($message, 'InProgress');

        return back()->with('success', 'Marked as In progress.');
    }

    /**
     * Closing the enquiry. The acting admin and the moment are stored, and
     * the note is kept so the next person can see what was actually done.
     */
    public function resolve(Request $req, $id)
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->status === 'Resolved') {
            return back()->with('error', 'This message is already resolved.');
        }

        $validated = $req->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!empty($validated['admin_notes'])) {
            $message->admin_notes = $validated['admin_notes'];
        }

        $message->resolved_by_user_id = auth()->id();
        $message->resolved_at         = now();

        $this->transition($message, 'Resolved');

        return back()->with('success', 'Message resolved.');
    }

    public function spam($id)
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->status === 'Spam') {
            return back()->with('error', 'This message is already marked as spam.');
        }

        $this->transition($message, 'Spam');

        return back()->with('success', 'Marked as spam.');
    }

    /** Back to New, for a message that was closed or flagged by mistake. */
    public function reopen($id)
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->status === 'New') {
            return back()->with('error', 'This message is already open.');
        }

        $message->resolved_by_user_id = null;
        $message->resolved_at         = null;

        $this->transition($message, 'New');

        return back()->with('success', 'Message reopened.');
    }

    /** Notes can be edited at any point without changing the status. */
    public function saveNotes(Request $req, $id)
    {
        $message = ContactMessage::findOrFail($id);

        $validated = $req->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $message->admin_notes = $validated['admin_notes'] ?? null;
        $message->save();

        return back()->with('success', 'Internal notes saved.');
    }

    /**
     * Permanent removal, allowed only for a message already parked in Spam.
     * A real enquiry is kept even after it is resolved.
     */
    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->status !== 'Spam') {
            return back()->with('error', 'Mark this message as spam before deleting it.');
        }

        $this->logAdminAction('contact_message.deleted', [
            'message_id' => $message->id,
            'from'       => $message->email,
        ]);

        $message->delete();

        return redirect('Admin/ContactMessages')
            ->with('success', 'Spam message deleted permanently.');
    }

    // ------------------------------------------------------------------

    /**
     * Single place where the status is written.
     *
     * The value is checked against the ENUM whitelist rather than trusted
     * from the caller, and every change leaves an audit line.
     */
    private function transition(ContactMessage $message, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            abort(422, 'Unknown status.');
        }

        $previous = $message->status;

        // Assigned directly instead of through fill(), because admin_notes,
        // resolved_by_user_id and resolved_at are deliberately not in the
        // model's $fillable list: the public contact form must never be
        // able to set them.
        $message->status = $status;
        $message->save();

        $this->logAdminAction('contact_message.' . strtolower($status), [
            'message_id' => $message->id,
            'from'       => $previous,
            'to'         => $status,
        ]);
    }

    /** Counts per status, used for the inbox tabs. */
    private function statusCounts(): array
    {
        $rows = ContactMessage::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts = ['All' => array_sum($rows)];

        foreach (self::STATUSES as $status) {
            $counts[$status] = $rows[$status] ?? 0;
        }

        return $counts;
    }
}
