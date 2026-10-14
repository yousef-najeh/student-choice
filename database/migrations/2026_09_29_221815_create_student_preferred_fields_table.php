<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_preferred_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_of_study_id')->constrained('fields_of_study')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_profile_id', 'field_of_study_id'], 'student_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_preferred_fields');
    }
};
