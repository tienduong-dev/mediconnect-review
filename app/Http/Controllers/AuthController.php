<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class AuthController extends Controller
{
    public function loginProcess(Request $req)
    {
        $req->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt(['email' => $req->email, 'password' => $req->password])) {
            if (Auth::user()->user_type == 'Admin') {
                return redirect('/Admin/AdminDashboard');
            }
            if (Auth::user()->user_type == 'Patient') {
                return redirect('/Patient/PatientDashboard');
            }
            if (Auth::user()->user_type == 'Doctor') {
                return redirect('/Doctor/DoctorDashboard');
            }
        } else {
            return redirect('login')->with("loginFail", "username and password are wrong");
        }
    }

    public function registerProcess(Request $req)
    {
        $validated = $req->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'email' => 'required|email|max:255|unique:users,email',
            'number' => 'required|string|max:20|unique:users,number',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'email' => $validated['email'],
            'number' => $validated['number'],
            'password' => $validated['password'],
            'user_type' => 'Patient',
            'account_status' => 'Active',
        ]);

        return redirect('/login')
            ->with('success', 'Registration successful. Please login.');
    }

    public function logoutProcess()
    {
        Auth::logout();
        return redirect('/');
    }
}
