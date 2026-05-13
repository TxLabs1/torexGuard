<?php

use App\Models\Project;
use App\Models\Server;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Broadcast;

// Org-scoped feed: org.{orgId} (used by ThreatDetected, AlertCreated, etc).
Broadcast::channel('org.{orgId}', function ($user, $orgId) {
    return $user->organizations()->where('organizations.id', (int) $orgId)->exists();
});

// Legacy alias to keep existing dashboard JS working.
Broadcast::channel('organization.{orgId}', function ($user, $orgId) {
    return $user->organizations()->where('organizations.id', (int) $orgId)->exists();
});

// Project-level events (system bypasses org scope for this lookup).
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    OrganizationContext::asSystem();
    $project = Project::find($projectId);
    OrganizationContext::reset();

    return $project
        ? $user->organizations()->where('organizations.id', $project->organization_id)->exists()
        : false;
});

Broadcast::channel('server.{serverId}', function ($user, $serverId) {
    OrganizationContext::asSystem();
    $server = Server::find($serverId);
    OrganizationContext::reset();

    return $server
        ? $user->organizations()->where('organizations.id', $server->organization_id)->exists()
        : false;
});
