<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, deploying, completed, failed
            $table->timestamp('triggered_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('source')->default('unknown'); // app, api, unknown
            $table->timestamps();

            $table->index(['site_id', 'triggered_at']);
        });
    }
};
