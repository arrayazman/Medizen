<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. INSTITUTION SETTINGS
        Schema::create('institution_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hospital_name')->default('RUMAH SAKIT UMUM');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Image paths
            $table->string('logo_path')->nullable();
            $table->string('watermark_path')->nullable();
            $table->string('footer_path')->nullable();
            
            // Integrations
            $table->string('pacs_license_code')->nullable();
            $table->string('satusehat_client_id')->nullable();
            $table->string('satusehat_client_secret')->nullable();
            $table->string('satusehat_organization_id')->nullable();
            
            // License & Proprietary
            $table->string('app_license_key')->nullable();
            $table->text('license_signature')->nullable();
            $table->boolean('is_pro')->default(false);
            
            // Display Settings
            $table->foreignId('display_gallery_id')->nullable()->constrained('galleries')->nullOnDelete();
            $table->string('display_video_url')->nullable();
            $table->json('display_instructions')->nullable();
            $table->text('display_marquee_text')->nullable();
            
            $table->timestamps();
        });

        // 2. AUDIT TRAILS
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.
            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // 3. NOTIFICATIONS
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_trails');
        Schema::dropIfExists('institution_settings');
    }
};
