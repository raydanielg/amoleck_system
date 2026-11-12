<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_kpis', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('name');
            $t->string('category')->default('financial');
            $t->decimal('target', 15, 2)->nullable();
            $t->string('unit')->default('value'); // value|percent|currency|count|days
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('management_insights', function (Blueprint $t) {
            $t->id();
            $t->string('key');
            $t->string('title');
            $t->string('category'); // financial|operational|patients|hr|inventory|branch
            $t->string('severity')->default('info'); // info|warning|critical
            $t->text('detail');
            $t->json('data')->nullable();
            $t->string('status')->default('new'); // new|acknowledged|dismissed
            $t->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('acknowledged_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_insights');
        Schema::dropIfExists('management_kpis');
    }
};
