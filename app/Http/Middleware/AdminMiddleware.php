<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Chua dang nhap hoac khong phai Admin
        if (!$user || $user->user_type !== 'Admin') {
            Auth::logout();
            return redirect('login')->with('msg', 'Admin login required');
        }

        // Tai khoan bi khoa boi Admin khac
        if ($user->account_status !== 'Active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('login')->with('msg', 'Tai khoan cua ban dang bi khoa. Vui long lien he quan tri vien.');
        }

        return $next($request);
    }
}
