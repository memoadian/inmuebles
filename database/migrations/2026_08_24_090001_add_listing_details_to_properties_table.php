<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Datos comerciales: sólo para el equipo, nunca para el catálogo público.
            $table->boolean('is_exclusive')->default(false)->after('is_featured');
            $table->enum('rent_commission', ['half_month', 'one_month', 'two_three_months'])
                ->nullable()->after('is_exclusive');
            $table->decimal('sale_commission_percent', 4, 2)->nullable()->after('rent_commission');

            // Ficha técnica.
            $table->unsignedSmallInteger('floor_number')->nullable()->after('floors');
            $table->enum('condition', ['nueva', 'excelente', 'buena', 'regular'])
                ->nullable()->after('age_years');
            $table->enum('orientation', [
                'norte', 'sur', 'oriente', 'poniente',
                'noreste', 'noroeste', 'sureste', 'suroeste',
            ])->nullable()->after('condition');
            $table->enum('position', ['interior', 'exterior'])->nullable()->after('orientation');

            // Desglose de costos, además del mantenimiento que ya existía.
            $table->decimal('property_tax_estimate', 10, 2)->nullable()->after('maintenance_fee');
            $table->decimal('services_estimate', 10, 2)->nullable()->after('property_tax_estimate');

            $table->index('is_exclusive');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['is_exclusive']);
            $table->dropColumn([
                'is_exclusive', 'rent_commission', 'sale_commission_percent',
                'floor_number', 'condition', 'orientation', 'position',
                'property_tax_estimate', 'services_estimate',
            ]);
        });
    }
};
