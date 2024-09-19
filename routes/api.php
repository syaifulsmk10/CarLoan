<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\DataApplicantController;
use App\Http\Controllers\ApplicantAdminController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/export/applicants', [DataApplicantController::class, 'index']);
    
    Route::get('/navbar', function() {
        if (Auth::check() && (Auth::user()->role->id == 1 || Auth::user()->role_id == 2)) {
            $user = User::with('role')->where('id', Auth::user()->id)->first();
    
            // Periksa apakah pengguna ditemukan
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }
    
            // Menyusun data detail pengguna
            $dataUser = [
                'id' => $user->id,
                'FirstName' => $user->FirstName,
                'LastName' => $user->LastName,
                'FullName' => $user->FirstName . ' ' . $user->LastName,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'rolename' => $user->role ? $user->role->name : null,
                'path' => $user->path ? env('APP_URL') . 'uploads/profiles/' . $user->path : null,
            ];
    
            return response()->json([
                'data' => $dataUser,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Your Login Not Admin'
            ], 403);
        }
    });
    
//crud User  
Route::get('/users', [UserController::class, 'getAllUser']);  //done
Route::get('/users/detail/{id}', [UserController::class, 'getUser']); //detail && get input done
Route::post('/users/create', [UserController::class, 'addUser']); //done
Route::post('/users/update/admin/{id}', [UserController::class, 'updateadmin']); //done//done
Route::post('/users/update/{id}', [UserController::class, 'updateUser']); //done//done
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