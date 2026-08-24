<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            // amenidad | recreacion | caracteristica
            $table->string('group', 40)->default('amenidad')->after('name');
            $table->unsignedSmallInteger('order')->default(0)->after('icon');

            $table->index(['group', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex(['group', 'order']);
            $table->dropColumn(['group', 'order']);
        });
    }
};
