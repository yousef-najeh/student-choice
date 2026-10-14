<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('gpa', 5, 2)->nullable();
            $table->foreignId('tawjihi_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('preferred_region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('preferred_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
