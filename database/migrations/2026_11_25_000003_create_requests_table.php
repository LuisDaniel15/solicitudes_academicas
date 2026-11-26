<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('category_id')->constrained('categories');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->enum('status', [
                'REGISTRADA', 'ASIGNADA', 'EN_PROCESO', 'RESUELTA',
                'CERRADA', 'RECHAZADA', 'REABIERTA',
            ])->default('REGISTRADA');
            $table->timestamps();

            $table->index('status');
            $table->index('category_id');
            $table->index('assigned_to');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
