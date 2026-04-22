<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. RADIOLOGY ORDERS
        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->string('accession_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('modality', 50);
            $table->foreignId('examination_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('referring_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('radiographer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('study_instance_uid', 128)->unique();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->dateTime('waktu_sample')->nullable();
            $table->dateTime('waktu_mulai_periksa')->nullable();
            $table->dateTime('waktu_selesai_periksa')->nullable();
            $table->string('station_ae_title', 64)->nullable();
            $table->text('procedure_description')->nullable();
            $table->string('clinical_info')->nullable();
            $table->enum('priority', ['ROUTINE', 'URGENT', 'STAT'])->default('ROUTINE');
            $table->enum('status', [
                'ORDERED',
                'SENT_TO_PACS',
                'IN_PROGRESS',
                'COMPLETED',
                'REPORTED',
                'VALIDATED',
                'CANCELLED'
            ])->default('ORDERED');
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Satusehat Bridge
            $table->string('satusehat_service_request_id')->nullable();
            $table->string('satusehat_specimen_id')->nullable();
            $table->string('satusehat_observation_id')->nullable();
            $table->string('satusehat_diagnostic_report_id')->nullable();
            
            $table->string('origin_system')->nullable()->default('INTERNAL');
            $table->json('informed_consent_data')->nullable();
            $table->string('access_token')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('scheduled_date');
            $table->index('status');
            $table->index('modality');
        });

        // 2. RADIOLOGY RESULTS
        Schema::create('radiology_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->longText('expertise')->nullable();
            $table->dateTime('waktu_hasil')->nullable();
            $table->enum('status', ['DRAFT', 'FINAL'])->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. RADIOLOGY TEMPLATES
        Schema::create('radiology_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_number')->unique();
            $table->string('examination_name');
            $table->text('expertise');
            $table->timestamps();
        });

        // 4. STUDY METADATA
        Schema::create('study_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_order_id')->constrained()->cascadeOnDelete();
            $table->string('sop_instance_uid')->nullable();
            $table->string('series_instance_uid')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 5. WORKLIST LOGS
        Schema::create('worklist_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_order_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('ae_title')->nullable();
            $table->timestamps();
        });

        // 6. GALLERIES
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 7. GALLERY ITEMS
        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('worklist_logs');
        Schema::dropIfExists('study_metadata');
        Schema::dropIfExists('radiology_templates');
        Schema::dropIfExists('radiology_results');
        Schema::dropIfExists('radiology_orders');
    }
};
