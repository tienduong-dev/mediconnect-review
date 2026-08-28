<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LocalController;
use App\Http\Controllers\PatientController;


// ================ Patient Register Router's ================
Route::get('/register', function () {
    return view('registerPage');
});
Route::post('/registerNow', [AuthController::class, 'registerProcess']);


// ================ Global Login & Logout Router's ================
Route::get('/login', function () {
    return view('loginPage');
});
Route::post('/loginNow', [AuthController::class, 'loginProcess']);
Route::get('/Admin/AdminDashboard', [AdminController::class, 'dashboard'])->middleware(['isAdmin']);
Route::get('/Patient/PatientDashboard', [PatientController::class, 'dashboard'])->middleware(['isPatient']);
Route::get('/Doctor/DoctorDashboard', [DoctorController::class, 'dashboard'])->middleware(['isDoctor']);
Route::get('/logout', [AuthController::class, 'logoutProcess']);


// Forgot Password
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

// Reset Password
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');



Route::middleware(['isAdmin'])->group(function () {
    // ================ Admin Router's ================
    // ------ Admin Patients Control Router's ------
    Route::get('Admin/Patients', [AdminController::class, 'patientList']);
    Route::get('Admin/Patient/DeleteThis/{id}', [AdminController::class, 'deleteThisPatient']);

    // ------ Admin Doctor Control Router's ------
    Route::get('Admin/DoctorRegister', function () {
        return view('admin.AdminDoctorRegister');
    });
    Route::post('Admin/RegisterThisDoctorNow', [AdminController::class, 'registerDoctor']);
    Route::get('Admin/Doctors', [AdminController::class, 'doctorsList']);
    Route::get('Admin/DoctorProfile/{id}', [AdminController::class, 'getThisDoctorProfile']);
    Route::get('Admin/Doctor/DeleteThis/{id}', [AdminController::class, 'deleteThisDoctor']);
    Route::get('Admin/AdminDoctorDetailsForm/{id}', [AdminController::class, 'getAddDoctorDetailsFormData']);
    Route::post('Admin/AddThisDoctorDetailsNow', [AdminController::class, 'saveDoctorDetails']);
    Route::get('Admin/Doctor/EditThisProfile/{id}', [AdminController::class, 'getAdminEditDoctorDetailsFormData']);
    Route::post('Admin/Doctor/SaveThisEditedDetailsNow', [AdminController::class, 'saveThisDoctorDetails']);
    Route::post('Admin/Doctor/updateStatus', [AdminController::class, 'updateDoctorStatus']);
    Route::get('Admin/Appointments', [AdminController::class, 'getAppointmentPage']);


    // ------ Admin Appointment Control Router's ------
    Route::post('Admin/Appointment/UpdateStatus', [AdminController::class, 'updateAppointmentStatus']);
});

Route::middleware(['isDoctor'])->group(function () {
    // ================ Doctor Router's ================
    Route::get('Doctor/ShowDoctorDetailsForm', [DoctorController::class, 'doctorCollectDataForm']);
    Route::post('Doctor/SaveDoctorDetailsNow', [DoctorController::class, 'saveDoctorDetails']);
    Route::get('Doctor/MyProfile', [DoctorController::class, 'getDoctorProfile']);
    Route::get('Doctor/EditProfile/{id}', [DoctorController::class, 'getEditDoctorForm']);
    Route::post('Doctor/SaveEditedInformationNow', [DoctorController::class, 'saveEditedDoctorDetails']);
    Route::get('Doctor/Delete/{id}', [DoctorController::class, 'deleteDoctor']);
    Route::post('Doctor/Appointment/UpdateStatus', [DoctorController::class, 'updateAppointmentStatus']);
});

Route::middleware(['isPatient'])->group(function () {
    // ================ Patient Router's ================
    Route::get('Patient/MyProfile', [PatientController::class, 'patientProfile']);
    Route::get('Patient/EditProfile/{id}', [PatientController::class, 'editPatientForm']);
    Route::post('Patient/EditThisProfile', [PatientController::class, 'editPatient']);
    Route::get('Patient/Delete/{id}', [PatientController::class, 'deletePatient']);
    Route::get('Patient/AppointmentHistory', [PatientController::class, 'getAppointmentHistory']);
    Route::post('Patient/Appointment/UpdateStatus', [PatientController::class, 'appointmentCancel']);
});

// views routers 
Route::get('/doctors', [LocalController::class, 'getDoctorsList']);
Route::get('/doctorDetails/{id}', [LocalController::class, 'getThisDoctorDetails']);

Route::get('FilterDoctors', [LocalController::class, 'getDoctorListForFilter']);
Route::post('getAppointment', [LocalController::class, 'getAppointmentForm']);
Route::post('BookAppointmentNow', [LocalController::class, 'saveAppointment']);
// Route::get('Appointment', [LocalController::class, 'getDoctorListForFilter']);



Route::get('/', function () {
    return view('index');
});
Route::get('/index', function () {
    return view('index');
});
Route::get('/index2', function () {
    return view('index2');
});
Route::get('/index3', function () {
    return view('index3');
});
Route::get('/about', function () {
    return view('about');
});
Route::get('/contact', function () {
    return view('contact');
});
