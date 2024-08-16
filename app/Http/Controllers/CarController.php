<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Car;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getCar()
    {
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
                'path' => $Cars->path ? env('APP_URL') . 'uploads/profiles' . $Cars->path : null,  
            ];

        }

     

        return response()->json([
            'data' => $datacar,

        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function update(Request $request, Car $car)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $car)
    {
        //
    }
}
