<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('password');
            $table->string('timezone')->default('UTC')->after('avatar');
            $table->string('two_factor_secret')->nullable()->after('timezone');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            $table->enum('role', ['superadmin', 'admin', 'developer', 'viewer'])
                ->default('developer')->after('two_factor_enabled');
            $table->timestamp('last_login_at')->nullable()->after('role');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->boolean('is_active')->default(true)->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'timezone',
                'two_factor_secret',
                'two_factor_enabled',
                'role',
                'last_login_at',
                'last_login_ip',
                'is_active',
            ]);
        });
    }
};
