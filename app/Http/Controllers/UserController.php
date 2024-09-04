<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UserController extends Controller
{public function qrLogin(Request $request)
    {
        // Temukan user dengan role_id 1 (Admin)
        $adminUser = User::where('role_id', 1)->first();
    
        if (!$adminUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin user not found',
            ], 404);
        }
    
        // Buat token autentikasi untuk Admin
        $token = $adminUser->createToken('auth')->plainTextToken;
    
        // Buat URL login dengan token yang sudah ada
        $loginUrl = route('login', ['token' => $token]);
    
        // Generate QR code dengan URL login
        $qrCode = QrCode::format('svg')->size(300)->generate($loginUrl);
    
        // Tentukan path ke direktori public
        $path = public_path('images/qrcodes/login_qr.svg');
    
        // Simpan file QR code
        file_put_contents($path, $qrCode);
    
        return response()->json([
            'status' => 'success',
            'message' => 'QR code generated successfully',
            'url' => asset('images/qrcodes/login_qr.svg'),
            'token' => $token,
        ]);
    }


    
    
    public function qrpagelogin(Request $request)
{
    $loginUrl = route('login'); // URL halaman login
//     $qrCode = QrCode::format('svg')
//     ->size(800)  // Ukuran lebih besar
//     ->margin(0)  // Menghilangkan margin default
//     ->generate($loginUrl);

// // Menambahkan styling SVG agar berada di tengah
// $qrCode = str_replace('<svg', '<svg style="display: block; margin: auto;"', $qrCode);

$qrCode = QrCode::format('svg')->size(300)->generate($loginUrl);

    // Tentukan path ke direktori public
    $directory = public_path('images/qrpathcodes');
    $path = $directory . '/login_qr.svg';

    // Pastikan direktori ada
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    // Simpan file QR code
    file_put_contents($path, $qrCode);

    return response()->json([
        'status' => 'success',
        'message' => 'QR code generated successfully',
        'url' => asset('images/qrpathcodes/login_qr.svg'),
    ]);
}

    


    public function postLogin(Request $request)
    {
        $validate = $request->validate([
            "email" => 'required|email',
            "password" => "required",
        ]);

        if (!Auth::attempt($validate)) {
            return response()->json([
                'message' => 'Wrong email or password',
                'data' => $validate
            ], 404);
        }


        $user = Auth::user();
        $token = $user->createToken('auth')->plainTextToken;
        $userData = $user->toArray(); //

        if ($user->role_id == 1) {
            return response()->json([
                'message' => 'Success Login Admin',
                'data' => $userData,
                'token' => $token
            ], 200);
        }

        return response()->json([
            'message' => 'Success Login User',
            'data' => $userData,
            'token' => $token
        ], 200);
    }

    public function addUser(Request $request)
    {
        // Validate request data
        if(Auth::user()->role->id == 1){
            $validator = Validator::make($request->all(), [
                'FirstName' => 'required|string|max:255',
                'LastName' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'path.*' => 'required|file|image|max:2048' // Validation for multiple images
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }
        
            // Create new user
           
        
            // Handle image upload
            if ($request->hasFile('path')) {
                $image = $request->file('path');
                $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalName();
                $image->move(public_path('uploads/profiles'), $imageName);
                $imagePath = $imageName; // Store the image path
            }else{
                return response()->json([
                    'message' => 'path cant null',
                ], 200);
            }

            $user = User::create([
                "FirstName" => $request->FirstName,
                "LastName" => $request->LastName,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "role_id" => 2,
            ]);
        
            // Update user with image path
            $user->update([
                'path' => $imagePath,
            ]);
        
            return response()->json([
                'message' => 'User created successfully',
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
    }
    

    public function updateUser(Request $request, $id) {

        if(Auth::user()->role->id == 1){
            $user = User::where('id',$id)->where('role_id',2)->first();
        
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }
        
            // Validate request data
            $validator = Validator::make($request->all(), [
                'FirstName' => 'sometimes|string|max:255',
                'LastName' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8|confirmed',
                'path' => 'nullable|file|image|max:2048' // Validation for single image
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }
        
            // Update user data
            if ($request->FirstName) {
                $user->FirstName = $request->FirstName;
            }
        
            if ($request->LastName) {
                $user->LastName = $request->LastName;
            }
        
            if ($request->email) {
                $user->email = $request->email;
            }
        
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }

        
    
            if ($request->hasFile('path')) {
                $image = $request->file('path');
                $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/profiles'), $imageName);
                $imagePath = $imageName; 
        
        
                $user->path = $imagePath;
            }
        
            $user->save();
        
            return response()->json([
                'message' => 'User updated successfully',
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
      
    }

    public function deleteUser($id) {
        if(Auth::user()->role->id == 1){
            $user = User::where('id', $id)->where('role_id', 2)->first();
    
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }
        
            $user->delete();
        
            return response()->json([
                'message' => 'User deleted successfully',
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
    }

    public function getUser($id) {
        if(Auth::user()->role->id == 1){
            $user = User::with('role')->where('id',$id)->where('role_id',2)->first();

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

    public function getAllUser(Request $request) {
        // Ambil pengguna dengan relasi role dan lakukan pagination
        if (Auth::user()->role->id == 1) {
            $perPage = $request->input('per_page', 5);
            $search = $request->input('search');
    
            // Query untuk mengambil pengguna dengan relasi role dan melakukan filtering berdasarkan role_id dan pencarian
            $usersQuery = User::with('role')
                ->where('role_id', 2); // Tambahkan filter untuk role_id
    
            // Tambahkan pencarian jika ada
            if ($search) {
                $usersQuery->where(function ($q) use ($search) {
                    $q->where('FirstName', 'LIKE', "%{$search}%")
                        ->orWhere('LastName', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }
    
            // Lakukan pagination setelah filter diterapkan
            $users = $usersQuery->paginate($perPage);
            // Ubah data koleksi pengguna
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'FirstName' => $user->FirstName,
                    'lastname' => $user->LastName,
                    'Fullname' => $user->FirstName . ' ' . $user->LastName,
                    'email' => $user->email,
                    'role' => $user->role_id,
                    'rolename' => $user->role ? $user->role->name : null,
                    'path' => $user->path ? env('APP_URL') . 'uploads/profiles/' . $user->path : null,
                ];
            });
        
            // Kembalikan response JSON
            return response()->json([
                'data' => $users,
                'total_pages' => $users->lastPage(),
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
        }

        public function navbar($id) {
            if(Auth::user()->role->id == 1){
                $user = User::with('role')->where('id',$id)->first();
    
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


