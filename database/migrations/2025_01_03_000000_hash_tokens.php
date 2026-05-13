<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move project API keys and agent tokens from plaintext to SHA-256 hashed
 * storage. Plaintext is only ever returned to the caller once at issuance.
 *
 * Strategy:
 *  - Add api_key_hash + api_key_prefix to projects.
 *  - Add token_hash    + token_prefix    to agents.
 *  - Backfill existing rows by hashing whatever is in the old plaintext column.
 *  - Drop the unique index on the plaintext column, then drop the column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── projects ────────────────────────────────────────────
        Schema::table('projects', function (Blueprint $table) {
            $table->string('api_key_hash', 64)->nullable()->after('api_key');
            $table->string('api_key_prefix', 16)->nullable()->after('api_key_hash');
            $table->string('api_secret_hash', 64)->nullable()->after('api_secret');
        });

        DB::table('projects')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('projects')->where('id', $row->id)->update([
                    'api_key_hash' => hash('sha256', (string) $row->api_key),
                    'api_key_prefix' => mb_substr((string) $row->api_key, 0, 11),
                    'api_secret_hash' => hash('sha256', (string) $row->api_secret),
                ]);
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['api_key']);
            $table->dropColumn(['api_key', 'api_secret']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unique('api_key_hash');
            $table->index('api_key_prefix');
        });

        // ── agents ──────────────────────────────────────────────
        Schema::table('agents', function (Blueprint $table) {
            $table->string('token_hash', 64)->nullable()->after('token');
            $table->string('token_prefix', 16)->nullable()->after('token_hash');
        });

        DB::table('agents')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('agents')->where('id', $row->id)->update([
                    'token_hash' => hash('sha256', (string) $row->token),
                    'token_prefix' => mb_substr((string) $row->token, 0, 14),
                ]);
            }
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->unique('token_hash');
        });

        // ── api_tokens (user personal access tokens) ───────────
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('token_hash', 64)->nullable()->after('token');
            $table->string('token_prefix', 16)->nullable()->after('token_hash');
        });

        DB::table('api_tokens')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('api_tokens')->where('id', $row->id)->update([
                    'token_hash' => hash('sha256', (string) $row->token),
                    'token_prefix' => mb_substr((string) $row->token, 0, 12),
                ]);
            }
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->unique('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->dropUnique(['api_key_hash']);
            $table->dropIndex(['api_key_prefix']);
            $table->dropColumn(['api_key_hash', 'api_key_prefix', 'api_secret_hash']);
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->string('token')->nullable();
            $table->dropUnique(['token_hash']);
            $table->dropColumn(['token_hash', 'token_prefix']);
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('token')->nullable();
            $table->dropUnique(['token_hash']);
            $table->dropColumn(['token_hash', 'token_prefix']);
        });
    }
};
