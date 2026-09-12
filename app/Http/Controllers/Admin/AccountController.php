<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The signed-in admin's own account.
 *
 * Until now an admin could change every other account's password but not
 * their own, which left the seeded password in place for the life of the
 * project. This screen closes that gap.
 *
 * The id is always taken from the session, never from the request, so this
 * controller cannot be pointed at somebody else's account.
 */
class AccountController extends Controller
{
    use LogsAdminActions;

    public function show()
    {
        return view('admin.account.index', [
            'admin' => Auth::user(),
        ]);
    }

    /** Name, email and phone of the signed-in admin. */
    public function updateProfile(Request $req)
    {
        $admin = Auth::user();

        $validated = $req->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'number' => ['required', 'digits:10', Rule::unique('users', 'number')->ignore($admin->id)],
        ], [
            'email.unique'  => 'Another account already uses this email.',
            'number.digits' => 'Phone number must be exactly 10 digits.',
            'number.unique' => 'Another account already uses this phone number.',
        ]);

        $admin->name   = $validated['name'];
        $admin->email  = $validated['email'];
        $admin->number = $validated['number'];
        $admin->save();

        $this->logAdminAction('account.profile_updated', ['user_id' => $admin->id]);

        return back()->with('success', 'Your profile has been updated.');
    }

    /**
     * Password change.
     *
     * The current password is required, so someone who walks up to an
     * unlocked screen cannot lock the real admin out of their own account.
     * Every other session of this account is dropped afterwards: if the
     * password is being changed because it leaked, the thief's session has
     * to die with it.
     */
    public function updatePassword(Request $req)
    {
        $admin = Auth::user();

        $req->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required'  => 'Enter your current password.',
            'current_password.current_password' => 'That is not your current password.',
            'password.confirmed' => 'The two new passwords do not match.',
            'password.different' => 'The new password must be different from the current one.',
            'password.min'       => 'The new password must be at least 8 characters.',
        ]);

        $admin->password = $req->input('password');   // hashed by the model cast
        $admin->save();

        $currentSession = $req->session()->getId();

        DB::table('sessions')
            ->where('user_id', $admin->id)
            ->where('id', '!=', $currentSession)
            ->delete();

        // New session id for this browser too, so a stolen cookie is useless
        $req->session()->regenerate();

        $this->logAdminAction('account.password_changed', ['user_id' => $admin->id]);

        return back()->with('success', 'Password changed. Any other session signed in as you has been closed.');
    }

    /** Read-only overview of who else can administer the system. */
    public function admins()
    {
        return view('admin.account.admins', [
            'admins' => User::where('user_type', 'Admin')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'number', 'account_status', 'last_login_at']),
        ]);
    }
}
