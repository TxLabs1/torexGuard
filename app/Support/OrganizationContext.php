<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Resolves the "current organization" for a request lifecycle.
 *
 * - In an HTTP request: the first org of the authenticated user
 *   (or whatever the user has selected via setCurrent()).
 * - In a queue worker / agent ingestion endpoint / artisan command:
 *   call asSystem() so the OrganizationScope is bypassed.
 *
 * Always prefer querying through this rather than reaching for
 * Auth::user()->organizations()->first() at every callsite.
 */
class OrganizationContext
{
    protected static ?int $currentId = null;
    protected static bool $systemMode = false;

    /** @var array<int,int|null> */
    protected static array $userOrgCache = [];

    public static function setCurrent(?int $organizationId): void
    {
        self::$currentId  = $organizationId;
        self::$systemMode = false;
    }

    public static function currentId(): ?int
    {
        if (self::$systemMode) {
            return null;
        }

        if (self::$currentId !== null) {
            return self::$currentId;
        }

        if (Auth::check()) {
            $userId = Auth::id();

            if (! array_key_exists($userId, self::$userOrgCache)) {
                self::$userOrgCache[$userId] = Auth::user()->organizations()
                    ->orderBy('organization_user.created_at')
                    ->value('organizations.id');
            }

            return self::$userOrgCache[$userId];
        }

        return null;
    }

    public static function asSystem(): void
    {
        self::$systemMode = true;
        self::$currentId  = null;
    }

    public static function reset(): void
    {
        self::$systemMode  = false;
        self::$currentId   = null;
        self::$userOrgCache = [];
    }
}
