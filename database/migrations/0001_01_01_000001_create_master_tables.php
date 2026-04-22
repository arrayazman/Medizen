<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. DOCTORS
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->string('phone')->nullable();
            $table->string('sip_number')->unique()->nullable();
            $table->string('simrs_kd_dokter', 20)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. RADIOGRAPHERS
        Schema::create('radiographers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('sip_number')->unique()->nullable();
            $table->string('simrs_kd_petugas', 20)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. MODALITIES
        Schema::create('modalities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('ae_title', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->integer('port')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. ROOMS
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->foreignId('modality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('floor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. EXAMINATION TYPES (Tarifa included in consolidated rows)
        Schema::create('examination_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('modality', 10)->nullable(); // Backup string code
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(15);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('js_rs', 12, 2)->default(0);
            $table->decimal('paket_bhp', 12, 2)->default(0);
            $table->decimal('jm_dokter', 12, 2)->default(0);
            $table->decimal('jm_petugas', 12, 2)->default(0);
            $table->decimal('jm_perujuk', 12, 2)->default(0);
            $table->decimal('kso', 12, 2)->default(0);
            $table->decimal('manajemen', 12, 2)->default(0);
            $table->string('jenis_bayar_kd', 20)->nullable();
            $table->string('jenis_bayar_nama', 100)->nullable();
            $table->string('kelas', 50)->nullable();
            $table->string('simrs_code', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. TARIFFS (Dedicated table if still needed separately, but often merged)
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('examination_types');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('modalities');
        Schema::dropIfExists('radiographers');
        Schema::dropIfExists('doctors');
    }
};
