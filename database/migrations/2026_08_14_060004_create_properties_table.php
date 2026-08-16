<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_type_id')->constrained();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->enum('operation', ['sale', 'rent', 'both'])->default('sale');
            $table->decimal('price', 14, 2)->default(0);
            $table->string('currency', 3)->default('MXN');
            $table->decimal('maintenance_fee', 10, 2)->nullable();

            $table->unsignedSmallInteger('bedrooms')->default(0);
            $table->unsignedSmallInteger('bathrooms')->default(0);
            $table->unsignedSmallInteger('half_bathrooms')->default(0);
            $table->unsignedSmallInteger('parking_spaces')->default(0);

            $table->decimal('land_area', 10, 2)->nullable();
            $table->decimal('built_area', 10, 2)->nullable();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->unsignedSmallInteger('age_years')->nullable();

            $table->string('street')->nullable();
            $table->string('ext_number', 20)->nullable();
            $table->string('int_number', 20)->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('neighborhood_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status', ['draft', 'published', 'reserved', 'sold', 'rented', 'inactive'])
                ->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'operation']);
            $table->index(['property_type_id', 'status']);
            $table->index(['state_id', 'city_id']);
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
