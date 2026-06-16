<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safe_git_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repository_id')->constrained('safe_git_repositories')->cascadeOnDelete();
            $table->string('operation');
            $table->text('command');
            $table->string('status');
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->integer('exit_code')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_git_operation_logs');
    }
};
