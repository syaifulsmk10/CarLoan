<?php

namespace App\Http\Controllers;

use App\Exports\ApplicantsExport;
use App\Models\Applicant;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class DataApplicantController extends Controller
{
    public function index(Request $request){

        if(Auth::user()->role->id == 1){
            $applicantQuery = Applicant::with('user');
            $applicant = $applicantQuery->get(); 


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

            // Filter berdasarkan status jika ada di request
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
        
    
    
            $perpage = $request->input("per_page", 5);
            $applicant = $applicantQuery->paginate($perpage);
            $totalpage = $applicant->lastPage();
    
            $Car = Car::all();
            if (!$Car) {
                return response()->json([
                    'message' => "Car Not Found"
                ]);
            };
    
    
            $datacar = [];
            foreach($Car as $Cars){
                $datacar[] = [
                    'id' => $Cars->id,
                    'name' => $Cars->name_car,  
                    'status_name' =>  $Cars->status ,
                    'path' => $Cars->path ? env('APP_URL') . 'uploads/profiles/' . $Cars->path : null,  
                ];
    
            }
    
    
            $applicant->getCollection()->transform(function ($applicants) {
    
                return [
                    'id' => $applicants->id,
                    'user_id' => $applicants->user_id,  
                    'name' => $applicants->user->FirstName . ' ' . $applicants->user->LastName, 
                    'email' => $applicants->user->email,
                    'car_id' =>  $applicants->car_id ,
                    'path' => $applicants->user->path ? env('APP_URL') . 'uploads/profiles/' . $applicants->user->path : null,  
                    'purpose' => $applicants->purpose,
                    'submission_date' => $applicants->submission_date,
                    'expiry_date' => $applicants->expiry_date,
                    'status' => $applicants->status,
                    'notes' => $applicants->notes,
                ];
            });
    
    
         
    
            return response()->json([
                'car' => $datacar,
                'dataApplicant' => $applicant,
                'total_page' => $totalpage,
                
    
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
      
    }

public function exportApplicants(Request $request)
{
    if(Auth::user()->role->id == 1){
        $applicantQuery = Applicant::with('user');

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
                'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles/' . $applicant->user->path : null,
                'purpose' => $applicant->purpose,
                'submission_date' => $applicant->submission_date,
                'expiry_date' => $applicant->expiry_date,
                'status' => $applicant->status,
                'notes' => $applicant->notes,
            ];
        });
    
        return Excel::download(new ApplicantsExport($applicants), 'applicants.xlsx');
    }else{
        return response()->json([
            "message" => "Your Login Not Admin"
        ]);
    }
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




