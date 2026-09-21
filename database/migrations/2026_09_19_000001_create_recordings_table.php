<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('recordings'); // Drop if exists to avoid issues during migrate:fresh if modified
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->string('resi')->index();
            $table->string('platform')->nullable()->index();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])->default('queued')->index();

            $table->string('source_path', 500)->nullable();
            $table->string('final_path', 500)->nullable();

            $table->unsignedInteger('duration')->nullable(); // in seconds
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('mime_type', 100)->nullable();

            $table->timestamp('recorded_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
