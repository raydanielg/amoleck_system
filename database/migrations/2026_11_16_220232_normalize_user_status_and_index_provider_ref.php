<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historical rows stored 'ACTIVE' — normalize so status checks are uniform
        DB::table('users')->update(['status' => DB::raw('LOWER(status)')]);

        Schema::table('messages', function (Blueprint $table) {
            $table->index('provider_ref');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['provider_ref']);
        });
    }
};
