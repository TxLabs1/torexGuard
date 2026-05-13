<?php

namespace App\Http\Middleware;

use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mark the request as "system context" so the OrganizationScope global
 * scope does not filter Eloquent queries by organization_id.
 *
 * Required for agent ingestion routes where there is no authenticated
 * user, but the controller still needs to operate across orgs (writing
 * to whatever organisation owns the project/agent).
 */
class AsSystem
{
    public function handle(Request $request, Closure $next): Response
    {
        OrganizationContext::asSystem();

        return $next($request);
    }
}
