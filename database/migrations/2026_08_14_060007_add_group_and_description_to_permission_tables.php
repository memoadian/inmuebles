<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('group')->nullable()->after('guard_name');
            $table->string('description')->nullable()->after('group');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['group', 'description']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
