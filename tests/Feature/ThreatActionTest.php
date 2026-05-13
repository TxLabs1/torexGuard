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

class ThreatActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Threat $threat;

    protected function setUp(): void
    {
        parent::setUp();
        OrganizationContext::asSystem();

        $org  = Organization::create(['name' => 'A', 'slug' => 'a']);
        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'admin']);

        $project = Project::create([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'name'            => 'p', 'slug' => 'p', 'status' => 'active',
        ]);
        $server = Server::create([
            'organization_id' => $org->id,
            'project_id'      => $project->id,
            'name' => 's', 'hostname' => 's', 'os' => 'linux', 'status' => 'online',
        ]);

        $this->user   = $user;
        $this->threat = Threat::create([
            'threat_id'       => Threat::generateThreatId(),
            'organization_id' => $org->id,
            'project_id'      => $project->id,
            'server_id'       => $server->id,
            'type'            => 'malware', 'severity' => 'high',
            'title'           => 'evil', 'status' => 'active',
            'detected_at'     => now(),
        ]);

        OrganizationContext::reset();
    }

    public function test_user_can_resolve_a_threat_in_their_org(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('threats.resolve', $this->threat))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('threats', [
            'id'     => $this->threat->id,
            'status' => 'resolved',
        ]);
    }

    public function test_user_can_mark_false_positive(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('threats.false-positive', $this->threat))
            ->assertOk();

        $this->assertDatabaseHas('threats', [
            'id'     => $this->threat->id,
            'status' => 'false_positive',
        ]);
    }
}
