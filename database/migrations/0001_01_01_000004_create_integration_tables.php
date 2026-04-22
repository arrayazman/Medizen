<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. SATUSEHAT MAPPINGS
        Schema::create('satusehat_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('examination_code')->unique();
            $table->string('periksa_code_id')->nullable();
            $table->string('sampel_code_id')->nullable();
            $table->timestamps();

            $table->index('examination_code');
        });

        // 2. SATUSEHAT PERIKSA CODES
        Schema::create('satusehat_periksa_codes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code');
            $table->string('display');
            $table->string('system')->default('http://loinc.org');
            $table->timestamps();
        });

        // 3. SATUSEHAT SAMPEL CODES
        Schema::create('satusehat_sampel_codes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code');
            $table->string('display');
            $table->string('system')->default('http://snomed.info/sct');
            $table->timestamps();
        });

        // 4. SIMRS MODALITY MAPS
        Schema::create('simrs_modality_maps', function (Blueprint $table) {
            $table->id();
            $table->string('kd_jenis_prw')->unique();
            $table->string('nm_perawatan')->nullable();
            $table->string('modality_code', 10);
            $table->unsignedBigInteger('examination_type_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('examination_type_id')
                ->references('id')
                ->on('examination_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simrs_modality_maps');
        Schema::dropIfExists('satusehat_sampel_codes');
        Schema::dropIfExists('satusehat_periksa_codes');
        Schema::dropIfExists('satusehat_mappings');
    }
};
