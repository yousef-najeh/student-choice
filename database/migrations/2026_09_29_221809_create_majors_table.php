<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('degree_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('field_of_study_id')->nullable()->constrained('fields_of_study')->nullOnDelete();
            $table->string('name');
            $table->decimal('credit_hour_price', 8, 2)->nullable();
            $table->unsignedInteger('credit_hours')->nullable();
            $table->string('study_duration_text')->nullable();
            $table->decimal('admission_average', 5, 2)->nullable();
            $table->decimal('min_gpa_to_apply', 5, 2)->nullable();
            $table->string('admission_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('majors');
    }
};
