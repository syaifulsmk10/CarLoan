<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if(Auth::user()->role->id == 2){
            $applicantQuery = Applicant::where('user_id', Auth::user()->id);
    
    
            $search = $request->input('search');
            $applicantQuery->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%");
            });


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

    $applicant = $applicantQuery->get(); 

    $perpage = $request->input("per_page",  5);
    $applicant = $applicantQuery->paginate($perpage);
    $totalpage = $applicant->lastPage();

        $Car = Car::all();
        if (!$Car) {
            return response()->json([
                'message' => "Car Not Found"
            ]);
        }


        $dataCar = [];
        foreach($Car as $Cars){
            $datacar[] = [
                'id' => $Cars->id,
                'name' => $Cars->name_car,  
                'status_name' =>  $Cars->status ,
                'path' => $Cars->path ? env('APP_URL') . 'uploads/profiles/' . $Cars->path : null,  
            ];

        }

     

        return response()->json([
            'car' => $datacar,
            'Applicant' => $applicant,
            'total_page' => $totalpage,
            

        ], 200);

        }else{
            return response()->json([
                "message" => "Your Login Not user"
            ]);
        }
        

    }

    /**
     * Show the form for creating a new resource.
     */
    public function addApplicant(Request $request)
    {   


        if(Auth::user()->role->id == 2){
            $validator = Validator::make($request->all(), [
                'car_id' => 'required|exists:cars,id',  // Memastikan car_id ada di tabel cars
                'purpose' => 'required|string|max:255', // Validasi purpose sebagai string maksimal 255 karakter
               'submission_date' => 'required|date_format:Y-m-d\TH:i:s',
               'expiry_date' => 'required|date_format:Y-m-d\TH:i:s|after:submission_date',  // Memastikan expiry_date adalah tanggal valid dan setelah submission_date
            ]);
        
            // Jika validasi gagal, kembalikan respon error
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
            
                $car = Car::where('id', $request->car_id)->first();
                if($car){
                    if($car->status == 'Available' || $car->status == 'Pending'){
                        $applicant = Applicant::create([
                            'user_id' => Auth::user()->id,
                            'car_id' => $request->car_id,
                            'purpose' => $request->purpose,
                            'submission_date' => $request->submission_date,
                            'expiry_date' => $request->expiry_date,
                            'status' => "Belum Disetujui"
                        ]);
                
                        $car->status = 'Pending';
                        $car->save();
                
                        return response()->json([
                            'message' => "create applicant sucessfulluy"
                        ]);
                    }else{
                        return response()->json([
                            'message' => "create applicant failed"
                        ]);
                    }
                }
        
        }else{
            return response()->json([
                "message" => "Your Login Not user"
            ]);
        }
      
       
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * Display the specified resource.
     */
    public function show(Applicant $applicant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function updateApplicant(Request $request, $id)
    {   

        if(Auth::user()->role->id == 2){
            $validator = Validator::make($request->all(), [
                'car_id' => 'sometimes|exists:cars,id',  // Memastikan car_id ada di tabel cars jika diisi
                'purpose' => 'sometimes|string|max:255', // Validasi purpose sebagai string maksimal 255 karakter jika diisi
                'submission_date' => 'sometimes|date_format:Y-m-d\TH:i:s', // Memastikan submission_date adalah format tanggal yang valid jika diisi
                'expiry_date' => 'sometimes|date_format:Y-m-d\TH:i:s|after:submission_date', // Memastikan expiry_date adalah tanggal valid dan setelah submission_date jika diisi
            ]);
        
            // Jika validasi gagal, kembalikan respon error
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
        
                $applicant = Applicant::where('user_id', Auth::user()->id)->where('id', $id)->first();
        
                if (!$applicant) {
                    return response()->json([
                        'message' => 'Applicant not found.'
                    ], 404);
                }
                $oldCar = Car::find($applicant->car_id);
                $newCar = Car::where('id', $request->car_id)->whereIn('status', ['Available', 'Pending'])->first();
              
            
                if ($request->has('car_id')) {
                    if ($applicant->status == "Belum Disetujui" && ($newCar->status == 'Available' || $oldCar->status == 'Pending')) {
                        if (!$newCar) {
                            return response()->json(['message' => 'New car status invalid.'], 400);
                        }
                        if ($applicant->car_id == $request->car_id) {
                            $newCar->status = 'Pending';
                            $newCar->save();
                        } else {
                            $newCar->status = 'Pending';
                            $newCar->save();
                            if ($oldCar) {
                                $oldCar->status = 'Available';
                                $oldCar->save();
                            }
                            $applicant->car_id = $request->car_id;
                        }
                    }      
                } 
                if ($request->has('purpose')) {
                    $applicant->purpose = $request->purpose;
                }
        
                if ($request->has('submission_date')) {
                    $applicant->submission_date = $request->submission_date;
                }
        
                if ($request->has('expiry_date')) {
                    $applicant->expiry_date = $request->expiry_date;
                }
                $applicant->save();
        
                return response()->json(['message' => 'Applicant updated successfully.']);
        }else{
            return response()->json([
                "message" => "Your Login Not user"
            ]);
        }
       
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Applicant $applicant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteApplicant(Applicant $applicant, $id)
    {
        if(Auth::user()->role->id == 2){
            $applicant = Applicant::where('id', $id)->where('user_id', Auth::user()->id)->first();
       
            if (!$applicant || $applicant->delete_user != null) {
                return response()->json([
                    'message' => 'Applicant not found.'
                ], 404);
            }
    
            $Car = Car::find($applicant->car_id);
            if ($applicant->status == "Belum Disetujui") {
                $applicant->delete();
               if($Car){
                $Car->update([
                    'status' => "Available",
                ]);
               }
                return response()->json([
                    "message" =>  "Applicant Delete successfully",
                ]);
    
            }else {
                return response()->json([
                    "message" =>  "Applicant Delete Denied",
                ]);
            }
    
        }else{
            return response()->json([
                "message" => "Your Login Not user"
            ]);
        }
    }
    
        
    
        public function detail($id){
            // Temukan pelamar dengan ID dan pastikan pelamar milik pengguna yang sedang login
            if(Auth::user()->role->id == 2){
                $applicant = Applicant::with(['user', 'car'])
                ->where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();
        
            // Periksa apakah pelamar ditemukan
            if (!$applicant) {
                return response()->json([
                    'message' => "Applicant Not Found"
                ], 404);
            }
        
            // Format data pelamar
            $dataApplicant = [
                'id' => $applicant->id,
                'user_id' => $applicant->user_id,  
                'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName, 
                'email' => $applicant->user->email,
                'car' => [
                    'id' => $applicant->car->id,
                    'name' => $applicant->car->name_car,
                    'status_name' => $applicant->car->status,
                    'path' => $applicant->car->path ? env('APP_URL') . 'uploads/profiles/' . $applicant->car->path : null,
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
                "message" => "Your Login Not user"
            ]);
        }
            }
         
    } 
