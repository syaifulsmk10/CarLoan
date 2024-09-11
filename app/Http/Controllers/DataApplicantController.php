<?php

namespace App\Http\Controllers;

use App\Exports\ApplicantsExport;
use App\Models\Applicant;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DataApplicantController extends Controller
{
    public function index(Request $request) {

        if (!Auth::check()) {
            return response()->json([
                "message" => "User not authenticated"
            ], 401); // Status 401 untuk unauthorized
        }

        if(Auth::user()->role->id == 1){
            $applicantQuery = Applicant::with('user', 'car');
    
            $search = $request->input('search');
            $applicantQuery->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('FirstName', 'LIKE', "%{$search}%")
                          ->orWhere('LastName', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
    
            // Filter berdasarkan status
            if ($request->has('status')) {
                $status = $request->input('status');
                if (is_array($status)) {
                    $applicantQuery->whereIn('status', $status);
                } else {
                    $applicantQuery->where('status', $status);
                }
            }

            if ($request->has('car_id') && $request->input('car_id') !== null) {
                $carId = $request->input('car_id');
                $applicantQuery->where('car_id', $carId);
            }
    
            // Filter berdasarkan tanggal
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                $applicantQuery->whereBetween('submission_date', [$startDate, $endDate]);
            }
    
          
    
            // Paginate hasil
            $perpage = $request->input("per_page", 5);
            $applicant = $applicantQuery->paginate($perpage);
            $totalpage = $applicant->lastPage();
    
            // Ambil data mobil
            
        $Car = Car::all();
        if (!$Car) {
            return response()->json([
                'message' => "Car Not Found"
            ]);
        }

        $Car = Car::select('cars.id', 'cars.name_car', 'cars.status', 'cars.path', 
        DB::raw('MAX(applicants.submission_date) as latest_submission_date'))
->join('applicants', 'cars.id', '=', 'applicants.car_id')
->where('applicants.status', 'Disetujui') // Hanya applicant dengan status Disetujui
->groupBy('cars.name_car') // Kelompokkan berdasarkan name_car untuk mendapatkan satu mobil per jenis
->orderBy('latest_submission_date', 'desc') // Urutkan berdasarkan submission_date terbaru
->with(['applicants' => function ($query) {
$query->where('status', 'Disetujui')
   ->latest('submission_date') // Ambil applicant terbaru
   ->first();
}])
->get();

$datacar = [];
foreach ($Car as $car) {
// Dapatkan applicant terbaru jika ada
$lastApplicant = $car->applicants->first();
$borrower = $lastApplicant ? $lastApplicant->user->FirstName . ' ' . $lastApplicant->user->LastName : 'Tidak Ada';
$expiry = $lastApplicant ? $lastApplicant->expiry_date : 'Tidak Ada';

$datacar[] = [
'id' => $car->id,
'name' => $car->name_car,
'status_name' => $car->status,
'borrowed_by' => $borrower,  // Tambahkan info peminjam terakhir
'expiry_date' => $expiry,
'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
];
}

        

     
    
            $applicants = $applicantQuery->get()->transform(function ($applicant) {
                return [
                    'id' => $applicant->id,
                    'user_id' => $applicant->user_id,
                    'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName,
                    'email' => $applicant->user->email,
                    'car_id' => $applicant->car_id,
                    'car_name' =>    $applicant->car->name_car ,
                    'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles/' . $applicant->user->path : null,
                    'purpose' => $applicant->purpose,
                    'submission_date' => $applicant->submission_date,
                    'expiry_date' => $applicant->expiry_date,
                    'status' => $applicant->status,
                    'notes' => $applicant->notes,
                ];
            });

              // Cek apakah user meminta untuk export Excel
              if ($request->input('export') == 'excel') {
                return Excel::download(new ApplicantsExport($applicants), 'applicants.xlsx');
            }
    
            return response()->json([
                'car' => $datacar,
                'dataApplicant' => $applicants,
                'total_page' => $totalpage,
            ], 200);
        } else {
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
    }
    

    public function exportApplicants(Request $request)
    {
        $applicantQuery = Applicant::with('user', 'car');

        if ($request->has('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $applicantQuery->whereIn('status', $status);
            } else {
                $applicantQuery->where('status', $status);
            }
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $applicantQuery->whereBetween('submission_date', [$startDate, $endDate]);
        }

        $applicants = $applicantQuery->get()->transform(function ($applicant) {
            return [
                'id' => $applicant->id,
                'user_id' => $applicant->user_id,
                'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName,
                'email' => $applicant->user->email,
                'car_id' => $applicant->car_id,
                'car_name' =>    $applicant->car->name_car ,
                'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles' . $applicant->user->path : null,
                'purpose' => $applicant->purpose,
                'submission_date' => $applicant->submission_date,
                'expiry_date' => $applicant->expiry_date,
                'status' => $applicant->status,
                'notes' => $applicant->notes,
            ];
        });

        return Excel::download(new ApplicantsExport($applicants), 'applicants.xlsx');
    }


public function detailApplicant($id){
    if(Auth::user()->role->id == 1){
        $applicant = Applicant::with('user', 'car')->find($id);

        // Periksa apakah pelamar ditemukan
        if (!$applicant) {
            return response()->json([
                'message' => "Applicant Not Found"
            ], 404);
        }
    
        // Ambil data mobil yang terkait dengan pelamar
        $car = $applicant->car;
    
        // Format data pelamar
        $dataApplicant = [
            'id' => $applicant->id,
            'user_id' => $applicant->user_id,  
            'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName, 
            'email' => $applicant->user->email,
            'car' => [
                'id' => $car->id,
                'name' => $car->name_car,
                'status_name' => $car->status,
                'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
            ],
            'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles/' . $applicant->user->path : null,  
            'purpose' => $applicant->purpose,
            'submission_date' => $applicant->submission_date,
            'expiry_date' => $applicant->expiry_date,
            'status' => $applicant->status,
            'notes' => $applicant->notes,
        ];
    
        return response()->json([
            'dataApplicant' => $dataApplicant
        ], 200);
    }else{
        return response()->json([
            "message" => "Your Login Not Admin"
        ]);
    }
}

public function accepted($id){
    if(Auth::user()->role->id == 1){
        $applicant = Applicant::find($id);
                if (!$applicant) {
                    return response()->json([
                        "message" => "Applicant Not Found"
                    ]);
                }

                if ($applicant && $applicant->accepted_at === null && $applicant->denied_at === null) {
                    $car = Car::where('id', $applicant->car_id)->first(); 

                    if($car){
                        if($applicant->status == "Belum Disetujui" && $car->status == "Available" || $car->status  = "Pending"){
                            $applicant->update([
                                "accepted_at" => Carbon::now(),
                                'status' => "Disetujui",
                            ]);
                        
                        if($car){
                            $car->update([
                                "status" => "In Use"
                            ]);
                        }
                        
                        $Applicant_check = Applicant::where('car_id', $applicant->car_id)
                            ->where('id', '!=', $id)
                            ->whereNull('denied_at')
                            ->whereNull('accepted_at');

                            $Applicant_check->update([
                                'denied_at' => Carbon::now(),
                                'status' => "Ditolak", 
                                'notes' => "The asset is already borrowed by someone else"
                            ]);

                        }

                        return response()->json([
                            "message" => "Accept Applicant Successful"
                        ]);

                    }
                    else {
                        return response()->json([
                            "message" => "Applicant cannot be accepted because they have been accepted or rejected previously."
                        ], 400);
                    }
                }    
    }else{
        return response()->json([
            "message" => "Your Login Not Admin"
        ]);
    }
            
    }


    public function denied(Request $request, $id){
        if(Auth::user()->role->id == 1){
            $applicant = Applicant::find($id);
            if (!$applicant) {
                return response()->json([
                    "message" => "Applicant Not Found"
                ]);
            }
    
            if ($applicant && $applicant->accepted_at === null && $applicant->denied_at === null) {
    
               
                $applicant->update([
                    "denied_at" => Carbon::now(),
                    "status" => "Ditolak",
                    'notes' => $request->notes,
                ]);
    
                $car = Car::find($applicant->car_id);
    
                if($car){
                    $car->update([
                        "status" => "Available",
                    ]);
                }
    
                return response()->json([
                    "message" => "Denied Applicant Successfully"
                ]);
    
    
        }else {
                return response()->json([
                    "message" => "Applicant cannot be accepted as they have already been accepted or denied."
                ], 400);
            }
            
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
        }
       
}




