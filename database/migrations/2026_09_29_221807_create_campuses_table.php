<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A campus is one physical branch of an institution in one city. An institution
     * can have several (e.g. a main campus in one city and a branch in another), and
     * a major is linked to whichever campuses actually offer it (see major_campus).
     */
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campuses');
    }
};
