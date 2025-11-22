<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filelist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filelist_id')->constrained('filelists')->cascadeOnDelete();
            $table->string('filename');
            $table->boolean('requested')->default(false);
            $table->boolean('uploaded')->default(false);
            $table->string('uploaded_path')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->tinyInteger('camera')->nullable(); // 1 o 2
            $table->timestamps();
            $table->index(['filelist_id', 'filename']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filelist_items');
    }
};
