<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\AdminApplicantApproval;
use App\Models\AdminCar;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Car;
use App\Models\Status;
use App\Models\Applicant;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        Role::create([
            "name" => "admin",
        ]);
        Role::create([
            "name" => "user",
        ]);

    // Admin
    User::create([
        'FirstName' => 'Pak',
        'LastName' => 'Gandi',
        "email" => "Gandi@intiva.id",
        "password" => bcrypt("aselole123"),
        "role_id" => 1, // Menggunakan role_id
        "path" => "https://media.istockphoto.com/id/493855843/photo/sleepy-ocelot-close-up.webp?a=1&b=1&s=612x612&w=0&k=20&c=llA_ImCMavNJJisPzjK8-3FYrqtRk9EDr2qaDeBRC3s="
    ]);

    User::create([
        'FirstName' => 'Pak',
        'LastName' => 'Adhyt',
        "email" => "Adit@intiva.id",
        "password" => bcrypt("aselole123"),
        "role_id" => 1, // Menggunakan role_id
        "path" => "https://media.istockphoto.com/id/493855843/photo/sleepy-ocelot-close-up.webp?a=1&b=1&s=612x612&w=0&k=20&c=llA_ImCMavNJJisPzjK8-3FYrqtRk9EDr2qaDeBRC3s="
    ]);

    User::create([
        'FirstName' => 'Pak',
        'LastName' => 'Elfin',
        "email" => "Elfin@intiva.id",
        "password" => bcrypt("aselole123"),
        "role_id" => 1, // Menggunakan role_id
        "path" => "https://media.istockphoto.com/id/493855843/photo/sleepy-ocelot-close-up.webp?a=1&b=1&s=612x612&w=0&k=20&c=llA_ImCMavNJJisPzjK8-3FYrqtRk9EDr2qaDeBRC3s="
    ]);

    // Siswa
    User::create([
        'FirstName' => 'Syaiful',
        'LastName' => 'Nazar',
         "email" => "Syaiful@nazar.com",
        "password" => bcrypt("aselole123"),
        "role_id" => 2, // Menggunakan role_id
        "path" => "user.png"
    ]);

    User::create([
        'FirstName' => 'Nadiyah',
        'LastName' => 'Atikah',
         "email" => "Nadiyah@atikah.com",
        "password" => bcrypt("aselole123"),
        "role_id" => 2, // Menggunakan role_id
        "path" => "user.png"
    ]);

    // Car::create([
    //     'name_car' => 'Toyota Corolla',
    //     'status' => 'Available',
    //     'path' => 'images/toyota_corolla.jpg',
    // ]);

    // Car::create([
    //     'name_car' => 'Honda Civic',
    //     'status' => 'Available',
    //     'path' => 'images/honda_civic.jpg',
    // ]);

    // Applicant::create([
    //     'car_id' => 1, 
    //     'user_id' => 3, 
    //     'purpose' => 'Personal use',
    //     'submission_date' => '2024-08-01',
    //     'expiry_date' => '2025-08-01',
    //     'status' => 'Proses',
    // ]);

    // Applicant::create([
    //     'car_id' => 2, 
    //     'user_id' => 3, 
    //     'purpose' => 'Business use',
    //     'submission_date' => '2024-08-02',
    //     'expiry_date' => '2025-08-02',
    //     'status' => 'Proses',
    // ]);

    // AdminCar::create([
    //     'user_id' => 1,
    //     'car_id' => 1,
    // ]);

    // AdminCar::create([
    //     'user_id' => 2,
    //     'car_id' => 1,
    // ]);

    // AdminCar::create([
    //     'user_id' => 1,
    //     'car_id' => 2,
    // ]);

    // AdminCar::create([
    //     'user_id' => 2,
    //     'car_id' => 3,
    // ]);

    // AdminApplicantApproval::create([
    //     'user_id' => 2,
    //     'applicant_id' => 2,
    //     'approval_status' => 'Approved',
    //     'notes' => 'tidak ada'
    // ]);

  





    }
}
