<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $t) {
            $t->string('occupation')->nullable()->after('address');
            $t->string('religion')->nullable()->after('occupation');
            $t->string('tribe')->nullable()->after('religion');
        });

        Schema::create('pt_assessments', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('assessed_by')->constrained('users');
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('assessed_at')->useCurrent();

            // History
            $t->text('chief_complaint')->nullable();
            $t->text('medical_diagnosis')->nullable();
            $t->text('hpi')->nullable();                    // History of presenting illness
            $t->text('past_medical_history')->nullable();
            $t->text('family_social_history')->nullable();
            $t->text('current_medication')->nullable();
            $t->text('investigations')->nullable();

            // Examination
            $t->text('observation')->nullable();
            $t->json('vitals')->nullable();               // bp, hr, rr, temp, spo2, weight, height
            $t->text('chest_assessment')->nullable();
            $t->text('local_assessment')->nullable();
            $t->text('neurological_assessment')->nullable();
            $t->text('functional_assessment')->nullable();
            $t->text('summary_findings')->nullable();
            $t->text('pt_impression')->nullable();

            // Plan
            $t->text('treatment_aim')->nullable();
            $t->text('short_term_plan')->nullable();
            $t->text('long_term_plan')->nullable();
            $t->text('evaluation_followup')->nullable();
            $t->boolean('home_visit')->default(false);
            $t->text('home_visit_notes')->nullable();
            $t->text('prescribed_equipment')->nullable();

            $t->string('status')->default('completed');   // draft | completed | amended
            $t->timestamps();
            $t->index(['patient_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pt_assessments');
        Schema::table('patients', function (Blueprint $t) {
            $t->dropColumn(['occupation', 'religion', 'tribe']);
        });
    }
};
