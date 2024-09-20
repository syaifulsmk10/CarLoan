<?php

namespace App\Http\Controllers;

use App\Models\AdminCar;
use App\Models\Applicant;
use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getCar()
{
    // Ambil semua mobil beserta admin yang memiliki mobil tersebut
    $cars = Car::with('adminCars.user')->get(); // Mengambil mobil beserta admin yang terkait

    if ($cars->isEmpty()) {
        return response()->json([
            'message' => 'No cars found',
        ], 404);
    }

    // Format data untuk response
    $data = $cars->map(function ($car) {
        return [
            'id' => $car->id,
            'name' => $car->name_car,
            'status' => $car->status,
            'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
            'admins' => $car->adminCars->map(function ($adminCar) {
                return [
                    'admin_id' => $adminCar->user_id,
                    'admin_name' => $adminCar->user->FirstName . ' ' . $adminCar->user->LastName,
                ];
            }),
        ];
    });

    return response()->json([
        'data' => $data,
    ], 200);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Periksa apakah user adalah super admin (role id 1) atau admin lain (role id 2)
        if (in_array(Auth::user()->role->id, [1, 2])) {
            
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:Available,Pending,In Use,Maintenance,Denied', // Validasi status tertentu
                'name_car' => 'required|string|max:255',
                'path' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar
                'user_ids' => 'required|array', // Daftar ID admin
                'user_ids.*' => 'exists:users,id', // Validasi ID admin ada di tabel users
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            // Membuat mobil baru di tabel Cars
            $Car = Car::create([
                'status' => $request->status,
                'name_car' => $request->name_car,
            ]);
          
            // Proses file gambar jika di-upload
            if ($request->hasFile('path')) {
                $image = $request->file('path');
                $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalName();
                $image->move(public_path('uploads/profiles'), $imageName);
                $Car->update([
                    'path' => $imageName,
                ]);
            }
    
            // Simpan hubungan antara admin (user) dan mobil di tabel admin_cars menggunakan model AdminCar
            foreach ($request->user_ids as $adminId) {
                AdminCar::create([
                    'user_id' => $adminId, // ID admin yang memiliki mobil
                    'car_id' => $Car->id, // ID mobil yang baru saja dibuat
                ]);
            }
    
            return response()->json([
                'message' => 'Car created successfully and assigned to admins',
                'car' => $Car,
            ], 201);
    
        } else {
            return response()->json([
                "message" => "Your Login Not Admin"
            ], 403);
        }
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Car $car)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Car $car)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    // Periksa apakah user adalah super admin (role id 1) atau admin lain (role id 2)
    if (in_array(Auth::user()->role->id, [1, 2])) {
        
        // Validasi input
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:Available,Pending,In Use,Maintenance,Denied', // Validasi status tertentu
            'name_car' => 'sometimes|string|max:255',
            'path' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar
            'user_ids' => 'sometimes|array', // Daftar ID admin
            'user_ids.*' => 'exists:users,id', // Validasi ID admin ada di tabel users
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cari mobil berdasarkan ID
        $Car = Car::find($id);
        
        if (!$Car) {
            return response()->json([
                'message' => 'Car not found',
            ], 404);
        }

        // Perbarui informasi mobil jika ada
        if ($request->has('status')) {
            $Car->status = $request->status;

            // Jika status mobil menjadi 'Available', ubah status pengajuan menjadi 'Finished'
            if ($request->status === 'Available') {
                Applicant::where('car_id', $Car->id)
                    ->where('status', 'Process') // Hanya pengajuan yang statusnya 'Process' yang diubah
                    ->update(['status' => 'Finished']);
            }
        }

        if ($request->has('name_car')) {
            $Car->name_car = $request->name_car;
        }

        // Proses file gambar jika di-upload
        if ($request->hasFile('path')) {
            $image = $request->file('path');
            $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalName();
            $image->move(public_path('uploads/profiles'), $imageName);
            $Car->path = $imageName;
        }

        // Simpan perubahan
        $Car->save();

        // Hapus hubungan lama antara mobil dan admin
        AdminCar::where('car_id', $Car->id)->delete();

        // Simpan hubungan baru antara admin (user) dan mobil di tabel admin_cars
        if ($request->has('user_ids')) {
            foreach ($request->user_ids as $adminId) {
                AdminCar::create([
                    'user_id' => $adminId, // ID admin yang memiliki mobil
                    'car_id' => $Car->id, // ID mobil yang baru saja diperbarui
                ]);
            }
        }

        return response()->json([
            'message' => 'Car updated successfully',
            'car' => $Car,
        ], 200);

    } else {
        return response()->json([
            "message" => "Your Login Not Admin"
        ], 403);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function deleteCar($id)
    {
        // Periksa apakah user adalah super admin (role id 1) atau admin lain (role id 2)
        if (in_array(Auth::user()->role->id, [1, 2])) {
            
            // Temukan mobil berdasarkan ID
            $car = Car::find($id);
    
            // Jika mobil tidak ditemukan, kembalikan respon error
            if (!$car) {
                return response()->json([
                    'message' => 'Car not found'
                ], 404);
            }
    
            // Hapus hubungan antara mobil dan admin
            AdminCar::where('car_id', $id)->delete();
    
            // Hapus mobil dari tabel Cars
            $car->delete();
    
            return response()->json([
                'message' => 'Car deleted successfully'
            ], 200);
    
        } else {
            return response()->json([
                "message" => "Your Login Not Admin"
            ], 403);
        }
    }
    
    public function navbar() {
        if(Auth::user()->role->id == 1 || Auth::user()->role_id == 2){
            $user = User::with('role')->where("id", Auth::user()->id)->first();

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
         
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
       
    }
}
