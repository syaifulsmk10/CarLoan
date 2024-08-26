<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Car;
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
        if(Auth::user()->role->id == 1 || Auth::user()->role->id == 2 ){
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
                'data' => $datacar,
    
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
       
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {   
        if(Auth::user()->role->id == 1){
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|max:255',
                'name_car' => 'required|string|max:255',
                'path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi file gambar
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            $Car = Car::create([
                'status' => $request->status,
                'name_car' => $request->name_car,
            ]);
          
            if ($request->hasFile('path')) {
                $image = $request->file('path');
                $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalName();
                $image->move(public_path('uploads/profiles'), $imageName);
                $imagePath = $imageName; // Store the image path
            }
        
            // Update user with image path
            $Car->update([
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
        if(Auth::user()->role->id == 1){
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|max:255',
                'name_car' => 'required|string|max:255',
                'path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi file gambar
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            $Car = Car::findOrFail($id);
    
    
            $Car->update([
                'status' => $request->status,
                'name_car' => $request->name_car,
            ]);
        
            if ($request->hasFile('path')) {
                if ($Car->path && file_exists(public_path('uploads/profiles/' . $Car->path))) {
                    unlink(public_path('uploads/profiles/' . $Car->path));
                }
        
                $image = $request->file('path');
                $imageName = 'VA' . Str::random(40) . '.' . $image->getClientOriginalName();
                $image->move(public_path('uploads/profiles'), $imageName);
        
                $Car->update([
                    'path' => $imageName,
                ]);
            }
        
            return response()->json([
                'message' => 'Car updated successfully',
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteCar(Car $id)
    {
        if(Auth::user()->role->id == 1){
            $Car = Car::findOrFail($id);

            if (!$Car) {
                return response()->json([
                    'message' => "Car Not Found"
                ], 404);
            }
    
            // Delete image if exists
            if ($Car->path && file_exists(public_path('uploads/profiles/' . $Car->path))) {
                unlink(public_path('uploads/profiles/' . $Car->path));
            }
        
            // Delete the car record
            $Car->delete();
        
            return response()->json([
                'message' => 'Car deleted successfully',
            ], 200);
        }else{
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
     
    }
}
