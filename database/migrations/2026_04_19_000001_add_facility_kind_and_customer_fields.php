<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->string('indoor_facility_kind', 16)->default('court')->after('type');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_phone', 64)->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone']);
        });

        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn('indoor_facility_kind');
        });
    }
};
