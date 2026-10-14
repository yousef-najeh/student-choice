<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('major_tawjihi_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tawjihi_branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['major_id', 'tawjihi_branch_id'], 'major_branch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('major_tawjihi_branch');
    }
};
