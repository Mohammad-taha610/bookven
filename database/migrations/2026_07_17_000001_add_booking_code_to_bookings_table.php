<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_code', 128)->nullable()->after('id');
            $table->index('booking_code');
        });

        $rows = DB::table('bookings')->orderBy('id')->get(['id', 'slot_id', 'date']);
        foreach ($rows as $row) {
            $ymd = str_replace('-', '', (string) $row->date);
            $code = 'BV'.$ymd.'-'.$row->slot_id.'-'.$row->id;
            DB::table('bookings')->where('id', $row->id)->update(['booking_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['booking_code']);
            $table->dropColumn('booking_code');
        });
    }
};
