<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->enum('plan', ['free', 'developer', 'startup', 'enterprise'])->default('free');
            $table->timestamp('plan_expires_at')->nullable();
            $table->integer('max_servers')->default(1);
            $table->integer('max_projects')->default(3);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'developer', 'viewer'])->default('developer');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['laravel', 'python', 'node', 'other'])->default('laravel');
            $table->enum('environment', ['production', 'staging', 'development'])->default('production');
            $table->string('api_key')->unique();
            $table->string('api_secret');
            $table->integer('risk_score')->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address', 45)->nullable();
            $table->string('public_ip', 45)->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('arch')->nullable();
            $table->integer('cpu_count')->nullable();
            $table->bigInteger('memory_total')->nullable();
            $table->bigInteger('disk_total')->nullable();
            $table->enum('status', ['online', 'offline', 'warning', 'critical'])->default('offline');
            $table->float('cpu_usage')->default(0);
            $table->float('memory_usage')->default(0);
            $table->float('disk_usage')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('agent_id')->unique();
            $table->string('version');
            $table->string('token')->unique();
            $table->enum('status', ['active', 'inactive', 'outdated', 'error'])->default('inactive');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('threats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('threat_id')->unique();
            $table->enum('type', [
                'web_shell', 'malware', 'reverse_shell', 'crypto_miner',
                'backdoor', 'obfuscated_code', 'suspicious_upload',
                'process_injection', 'privilege_escalation', 'brute_force',
                'sql_injection', 'xss', 'rce', 'file_modification',
                'suspicious_process', 'outbound_connection', 'other'
            ])->default('other');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['active', 'investigating', 'quarantined', 'resolved', 'false_positive'])->default('active');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_hash')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('process_name')->nullable();
            $table->integer('process_id')->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->string('source_country')->nullable();
            $table->integer('confidence_score')->default(0);
            $table->json('detection_details')->nullable();
            $table->json('signatures_matched')->nullable();
            $table->boolean('auto_remediated')->default(false);
            $table->string('remediation_action')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'severity', 'status']);
            $table->index(['detected_at']);
        });

        Schema::create('threat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('threat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('file_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_hash');
            $table->bigInteger('file_size');
            $table->string('permissions', 10);
            $table->string('owner')->nullable();
            $table->timestamp('file_modified_at')->nullable();
            $table->boolean('is_baseline')->default(false);
            $table->enum('change_type', ['created', 'modified', 'deleted', 'permission_changed', 'owner_changed', 'baseline'])->default('baseline');
            $table->boolean('flagged')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'server_id', 'file_path']);
            $table->index(['flagged']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('channel', ['dashboard', 'email', 'slack', 'webhook', 'sms'])->default('dashboard');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_read']);
        });

        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('threat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->references('id')->on('users');
            $table->enum('type', [
                'quarantine_file', 'delete_file', 'chmod_lock',
                'kill_process', 'block_ip', 'disable_endpoint',
                'restore_file', 'send_alert', 'lock_environment'
            ]);
            $table->enum('status', ['pending', 'executing', 'success', 'failed', 'rolled_back'])->default('pending');
            $table->boolean('is_automated')->default(false);
            $table->text('target');
            $table->text('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token')->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical'])->default('info');
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->boolean('flagged')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['project_id', 'processed']);
            $table->index(['occurred_at']);
        });

        Schema::create('quarantine_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('threat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('original_path');
            $table->string('quarantine_path');
            $table->string('file_hash');
            $table->bigInteger('file_size');
            $table->enum('status', ['quarantined', 'restored', 'deleted'])->default('quarantined');
            $table->foreignId('quarantined_by')->nullable()->references('id')->on('users');
            $table->timestamp('quarantined_at');
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('slack_enabled')->default(false);
            $table->string('slack_webhook')->nullable();
            $table->boolean('webhook_enabled')->default(false);
            $table->string('webhook_url')->nullable();
            $table->json('notify_on')->nullable();
            $table->json('severity_threshold')->nullable();
            $table->timestamps();
        });

        Schema::create('scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('scan_id')->unique();
            $table->enum('type', ['full', 'quick', 'targeted', 'scheduled'])->default('full');
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->integer('files_scanned')->default(0);
            $table->integer('threats_found')->default(0);
            $table->integer('files_clean')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_results');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('quarantine_files');
        Schema::dropIfExists('events');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('file_snapshots');
        Schema::dropIfExists('threat_logs');
        Schema::dropIfExists('threats');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
