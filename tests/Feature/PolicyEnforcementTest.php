<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Server;
use App\Models\Threat;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_cannot_view_threats_from_other_organizations(): void
    {
        OrganizationContext::asSystem();

        $orgA = Organization::create(['name' => 'A', 'slug' => 'a']);
        $orgB = Organization::create(['name' => 'B', 'slug' => 'b']);

        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $orgA->users()->attach($alice->id, ['role' => 'admin']);
        $orgB->users()->attach($bob->id, ['role' => 'admin']);

        $projectB = Project::create([
            'organization_id' => $orgB->id,
            'user_id' => $bob->id,
            'name' => 'B-project',
            'slug' => 'b-project',
            'status' => 'active',
        ]);

        $serverB = Server::create([
            'organization_id' => $orgB->id,
            'project_id' => $projectB->id,
            'name' => 'b-srv',
            'hostname' => 'b-srv',
            'os' => 'linux',
            'status' => 'online',
        ]);

        $threatB = Threat::create([
            'threat_id' => Threat::generateThreatId(),
            'organization_id' => $orgB->id,
            'project_id' => $projectB->id,
            'server_id' => $serverB->id,
            'type' => 'malware',
            'severity' => 'high',
            'title' => 'evil',
            'status' => 'active',
            'detected_at' => now(),
        ]);

        OrganizationContext::reset();

        $this->actingAs($alice)
            ->get('/threats/'.$threatB->id)
            ->assertNotFound();
    }
}
