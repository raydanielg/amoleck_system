<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_name_snapshot');
            $table->string('service_code_snapshot')->nullable();
            $table->decimal('price_snapshot', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['visit_id']);
            $table->index(['service_name_snapshot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
    }
};
