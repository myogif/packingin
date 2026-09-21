<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recording_tag', function (Blueprint $table) {
            $table->foreignId('recording_id')->constrained('recordings')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();

            $table->primary(['recording_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recording_tag');
    }
};
