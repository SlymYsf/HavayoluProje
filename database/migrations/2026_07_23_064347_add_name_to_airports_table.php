<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('passenger_type', ['adult', 'child', 'infant', 'student'])
                ->default('adult')
                ->after('cabin_class');
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('passenger_type');
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};
