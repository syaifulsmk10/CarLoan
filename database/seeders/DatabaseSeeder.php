<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        'FirstName' => 'admin',
        'LastName' => 'Doe',
        "email" => "admin@admin.com",
        "password" => bcrypt("admin_password"),
        "role_id" => 1, // Menggunakan role_id
        "path" => "admin.png"
    ]);

    // Siswa
    User::create([
        'FirstName' => 'user',
        'LastName' => 'Doe',
         "email" => "user@user.com",
        "password" => bcrypt("user_password"),
        "role_id" => 2, // Menggunakan role_id
        "path" => "user.png"
    ]);

   

    Car::create([
        'name_car' => 'Toyota Corolla',
        'status' => 'Available',
        'path' => 'images/toyota_corolla.jpg',
    ]);

    Car::create([
        'name_car' => 'Honda Civic',
        'status' => 'Available',
        'path' => 'images/honda_civic.jpg',
    ]);

    Applicant::create([
        'car_id' => 1, 
        'user_id' => 1, 
        'purpose' => 'Personal use',
        'submission_date' => '2024-08-01',
        'expiry_date' => '2025-08-01',
        'status' => 'Disetujui',
        'notes' => 'First-time applicant',
    ]);

    Applicant::create([
        'car_id' => 2, 
        'user_id' => 2, 
        'purpose' => 'Business use',
        'submission_date' => '2024-08-02',
        'expiry_date' => '2025-08-02',
        'status' => 'Disetujui',
        'notes' => 'Frequent applicant',
    ]);





    }
}
