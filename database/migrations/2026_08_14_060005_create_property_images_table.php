<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            // El disco se guarda por imagen: permite desarrollar en 'public'
            // y producir en 'r2' sin migrar las filas existentes.
            $table->string('disk', 20)->default('public');
            $table->string('path');
            $table->string('thumb_path')->nullable();

            $table->string('original_name')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('mime', 60)->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_cover')->default(false);

            $table->timestamps();

            $table->index(['property_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
