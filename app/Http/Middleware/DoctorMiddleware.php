<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;
use App\Models\Doctor;

class DoctorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $doctor = $user ? Doctor::where('user_id', $user->id)->first() : null;
        if ($user && $user->user_type === 'Doctor' && $user->account_status === 'Active'
            && (!$doctor || $doctor->status === 'Active')) {
            return $next($request);
        } else {
            if ($user && $user->account_status !== 'Active') {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            Auth::logout();
            return redirect('login')->with('msg', 'Doctor login required or account/profile is inactive');
        }
    }
}
