<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->foreignId('server_id')->nullable()->after('project_id')
                ->constrained('servers')->nullOnDelete();

            // Allow stand-alone actions (force_scan, isolate_server) with no threat.
            $table->foreignId('threat_id')->nullable()->change();

            $table->timestamp('dispatched_at')->nullable()->after('executed_at');

            $table->index(['server_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropIndex(['server_id', 'status']);
            $table->dropColumn(['server_id', 'dispatched_at']);
            $table->foreignId('threat_id')->nullable(false)->change();
        });
    }
};
