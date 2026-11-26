<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('previous_assignee_id')->nullable()->constrained('users');
            $table->foreignId('new_assignee_id')->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_assignments');
    }
};
