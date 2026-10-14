<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('major_campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['major_id', 'campus_id'], 'major_campus_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('major_campus');
    }
};
