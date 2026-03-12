<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('ploi_id');
            $table->string('name');
            $table->string('ip_address')->nullable();
            $table->string('status')->default('unknown');
            $table->string('type')->nullable();
            $table->string('php_version')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'ploi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
