<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use Illuminate\Http\Request;

class UserController extends Controller
{
    
    public function qrLogin(Request $request)
{
    $token = $request->query('token');

    // Find the user by token
    $user = User::where('qr_token', $token)->first();

    if ($user) {
        // Log the user in
        Auth::login($user);

        // Clear the token
        $user->update(['qr_token' => null]);

        return response()->json(['message' => 'Login successful'], 200);
    }

    return response()->json(['message' => 'Invalid token'], 401);
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
                'path.*' => 'nullable|file|image|max:2048' // Validation for multiple images
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }
        
            // Create new user
            $user = User::create([
                "FirstName" => $request->FirstName,
                "LastName" => $request->LastName,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "role_id" => 2,
            ]);
        
            // Handle image upload
            if ($request->hasFile('path')) {
                $image = $request->file('path');
                $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalName();
                $image->move(public_path('uploads/profiles'), $imageName);
                $imagePath = $imageName; // Store the image path
            }
        
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
                $user->FirstName = $request->input('FirstName');
            }
        
            if ($request->LastName) {
                $user->LastName = $request->input('LastName');
            }
        
            if ($request->email) {
                $user->email = $request->input('email');
            }
        
            if ($request->password) {
                $user->password = Hash::make($request->input('password'));
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
        if(Auth::user()->role->id == 1){
            $perPage = $request->input('per_page', 100);
    
            $users = User::with('role')
            ->where('role_id', 2) // Tambahkan filter untuk role_id
            ->paginate($perPage);
        
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
       
    
}


