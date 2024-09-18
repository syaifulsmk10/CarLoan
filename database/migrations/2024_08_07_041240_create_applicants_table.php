<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('applicants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('car_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('purpose');
                $table->dateTime('submission_date');
                $table->dateTime('expiry_date');
                $table->enum('status', ['Pending', 'Process', 'Rejected', 'completed']);
                $table->dateTime('accepted_at')->nullable();
                $table->dateTime('denied_at')->nullable();
                $table->dateTime('delete_admin')->nullable();
                $table->dateTime('delete_user')->nullable();
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
