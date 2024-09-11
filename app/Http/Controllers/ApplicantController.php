<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if(Auth::user()->role->id == 2){
            $applicantQuery = Applicant::where('user_id', Auth::user()->id)
            ->orderBy('submission_date', 'asc');
    
    
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
    if ($request->has('car_id')) {
        $carId = $request->input('car_id');
        $applicantQuery->where('car_id', $carId);
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

    $latestApplicants = DB::table('applicants')
    ->select('car_id', DB::raw('MAX(submission_date) as latest_submission_date'))
    ->groupBy('car_id');

$cars = Car::with(['applicants' => function ($query) use ($latestApplicants) {
        $query->joinSub($latestApplicants, 'latest', function ($join) {
            $join->on('applicants.car_id', '=', 'latest.car_id')
                 ->on('applicants.submission_date', '=', 'latest.latest_submission_date');
        })
        ->where('status', 'Disetujui') // Ambil hanya applicant dengan status Disetujui
        ->orderBy('submission_date', 'desc'); // Urutkan berdasarkan submission date terbaru
    }, 'applicants.user'])->get();


       

        $datacar = [];
        foreach ($cars as $car) {
            // Dapatkan peminjam terakhir jika ada
            $lastApplicant = $car->applicants->first();
            $borrower = $lastApplicant ? $lastApplicant->user->FirstName . ' ' . $lastApplicant->user->LastName : 'Tidak Ada';
            $expiry = $lastApplicant ? $lastApplicant->expiry_date : 'Tidak Ada';

            if($car->status == "In Use"){
                $datacar[] = [
                    'id' => $car->id,
                    'name' => $car->name_car,
                    'status_name' => $car->status,
                    'borrowed_by' => $borrower,  // Tambahkan info peminjam terakhir
                    'expiry_date' => $expiry,
                    'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
                ];
            }  else{
                $datacar[] = [
                    'car_id' => $car->id,
                    'name_car' => $car->name_car,
                    'status' => $car->status,
                    'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
                ];
            }

           
        }

        $applicants = $applicantQuery->get()->transform(function ($applicant) {
            return [
                'id' => $applicant->id,
                'user_id' => $applicant->user_id,
                'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName,
                'email' => $applicant->user->email,
                'car_id' => $applicant->car_id,
                'car_name' =>    $applicant->car->name_car,
                'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles' . $applicant->user->path : null,
                'purpose' => $applicant->purpose,
                'submission_date' => $applicant->submission_date,
                'expiry_date' => $applicant->expiry_date,
                'status' => $applicant->status,
                'notes' => $applicant->notes,
            ];
        });

     

        return response()->json([
            'car' => $datacar,
            'Applicant' => $applicants,
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
               'submission_date' => 'required|date_format:Y-m-d\TH:i:s|after_or_equal:today',
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
                        ], 400);
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
                'car_id' => 'sometimes|exists:cars,id', 
                'purpose' => 'sometimes|string|max:255', 
                'submission_date' => 'sometimes|date_format:Y-m-d\TH:i:s', // Memastikan submission_date adalah format tanggal yang valid jika diisi
                'expiry_date' => 'sometimes|date_format:Y-m-d\TH:i:s|after_or_equal:today', // Memastikan expiry_date adalah tanggal valid dan setelah submission_date jika diisi
            ]);
        
            // Jika validasi gagal, kembalikan respon error
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
        
                $applicant = Applicant::where('user_id', Auth::user()->id)->where('id', $id)->where('status', 'Belum Disetujui')->first();
        
                if (!$applicant) {
                    return response()->json([
                        'message' => 'Applicant not found.'
                    ], 404);
                }
                $oldCar = Car::find($applicant->car_id);
                $newCar = Car::where('id', $request->car_id)->first();
              
            
                if ($request->has('car_id')) {

                    if ($applicant->status == "Belum Disetujui" &&  $newCar->status == 'In Use') {
                        return response()->json([
                            "message" => "Car In use"
                        ]);
                    }
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
    
            }elseif ($applicant->status == "Disetujui" || $applicant->status == "DiTolak") {
                 $applicant->delete();
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



    //mbilnya tambah status yang pinjem siapa //done
    //filter mobil ketika di klik ke filter //done
    //status mobil jadi dimunculinn   //done
    //checkbox user munculin semua //done
    //tanggal buat vaidasi tanggal //done
    //descending subbmisiion date  //done
    //filter export excell    //done             
