<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\DataApplicantController;
use App\Http\Controllers\ApplicantAdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("/login", [UserController::class, 'postLogin'])->name("login"); //done
Route::get('auth/qr-login', [UserController::class, 'qrLogin'])->name("qrLogin");; // For QR code login
Route::get('auth/qr-path-login', [UserController::class, 'qrpagelogin'])->name("qrpagelogin");; // For QR code login
Route::get('/export/applicants', [DataApplicantController::class, 'exportApplicants']);
Route::middleware('auth:sanctum')->group(function () {

//crud User  
Route::get('/users', [UserController::class, 'getAllUser']);  //done
Route::get('/users/detail/{id}', [UserController::class, 'getUser']); //detail && get input done
Route::post('/users/create', [UserController::class, 'addUser']); //done
Route::post('/users/update/{id}', [UserController::class, 'updateUser']); //done
Route::get('/navbar/profile', [UserController::class, 'navbar']); //done
Route::delete('/users/delete/{id}', [UserController::class, 'deleteUser']); //done

//Car

Route::get('/car', [CarController::class, 'getCar']); 
Route::post('/car', [CarController::class, 'create']);
Route::post('/car/{id}', [CarController::class, 'update']);
Route::delete('/car/{id}', [CarController::class, 'deleteCar']);

//Applicant

Route::get('/Applicant', [ApplicantController::class, 'index'])->name('index'); //done
Route::post('/Applicant/create', [ApplicantController::class, 'addApplicant']); //done
Route::post('/Applicant/update/{id}', [ApplicantController::class, 'updateApplicant']); //done
Route::delete('/Applicant/delete/{id}', [ApplicantController::class, 'deleteApplicant']); //done
Route::get('/Applicant/detail/{id}', [ApplicantController::class, 'detail'])->name('detail'); // detail && get inout done

//DataApplicant

Route::get('/data/applicants', [DataApplicantController::class, 'index'])->name('index'); //done
Route::post('/Applicant/accepted/{id}', [DataApplicantController::class, 'accepted'])->name('accepted'); //done
Route::post('/Applicant/denied/{id}', [DataApplicantController::class, 'denied'])->name('denied'); //done
Route::get('/data/applicants/{id}', [DataApplicantController::class, 'detailApplicant'])->name('detailApplicant'); //done






});