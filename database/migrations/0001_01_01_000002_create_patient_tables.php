<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. PATIENTS
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm', 64)->unique();
            $table->string('nik', 16)->nullable()->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('gol_darah', 5)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('pendidikan', 50)->nullable();
            $table->string('agama', 20)->nullable();
            $table->string('status_nikah', 20)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('nama_ibu', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('suku_bangsa', 50)->nullable();
            $table->string('bahasa', 50)->nullable();
            $table->string('cacat_fisik', 100)->nullable();
            
            // Military/Police Fields
            $table->boolean('is_tni')->default(false);
            $table->string('tni_golongan', 50)->nullable();
            $table->string('tni_kesatuan', 100)->nullable();
            $table->string('tni_pangkat', 50)->nullable();
            $table->string('tni_jabatan', 100)->nullable();
            $table->boolean('is_polri')->default(false);
            $table->string('polri_golongan', 50)->nullable();
            $table->string('polri_kesatuan', 100)->nullable();
            $table->string('polri_pangkat', 50)->nullable();
            $table->string('polri_jabatan', 100)->nullable();
            
            // PJ / Responsible Party
            $table->string('nama_pj', 100)->nullable();
            $table->string('png_jawab', 50)->nullable();
            $table->string('pekerjaan_pj', 100)->nullable();
            $table->string('alamat_pj', 255)->nullable();
            $table->string('kelurahan_pj', 100)->nullable();
            $table->string('kecamatan_pj', 100)->nullable();
            $table->string('kabupaten_pj', 100)->nullable();
            $table->string('provinsi_pj', 100)->nullable();
            
            $table->string('asuransi', 100)->nullable();
            $table->string('no_peserta', 50)->nullable();
            $table->date('tgl_daftar')->nullable();
            $table->string('instansi_pasien', 150)->nullable();
            $table->string('nip_nrp', 50)->nullable();
            
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. REGIONS
        Schema::create('provinces', function (Blueprint $table) {
            $table->char('id', 2)->primary();
            $table->string('name');
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->char('id', 4)->primary();
            $table->char('province_id', 2);
            $table->string('name');
            $table->foreign('province_id')->references('id')->on('provinces');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->char('id', 7)->primary();
            $table->char('regency_id', 4);
            $table->string('name');
            $table->foreign('regency_id')->references('id')->on('regencies');
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->char('id', 10)->primary();
            $table->char('district_id', 7);
            $table->string('name');
            $table->foreign('district_id')->references('id')->on('districts');
        });

        // 3. LAST MEDICAL RECORDS
        Schema::create('last_medical_records', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('last_medical_records');
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('patients');
    }
};
