<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A scholarship belongs to the student, not to any major — it's an award
     * admin grants a student (e.g. "50% off"), and it discounts whatever major
     * that student is matched against, not a fixed set of majors. See
     * docs/technical-flows.md for how this feeds into MajorMatchingService.
     */
    public function up(): void
    {
        Schema::create('student_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_profile_id', 'scholarship_id'], 'student_scholarship_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_scholarships');
    }
};
