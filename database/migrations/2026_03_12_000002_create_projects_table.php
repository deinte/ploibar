<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('ploi_id');
            $table->string('title');
            $table->timestamps();

            $table->unique(['account_id', 'ploi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
