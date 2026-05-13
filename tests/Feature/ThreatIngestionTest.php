<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Server;
use App\Models\Threat;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreatIngestionTest extends TestCase
{
    use RefreshDatabase;

    private string $agentToken;

    private Project $project;

    private Server $server;

    private Organization $org;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        OrganizationContext::asSystem();

        $this->org = Organization::create(['name' => 'Acme', 'slug' => 'acme']);
        $this->owner = User::factory()->create();
        $this->org->users()->attach($this->owner->id, ['role' => 'admin']);

        $this->project = Project::create([
            'organization_id' => $this->org->id,
            'user_id' => $this->owner->id,
            'name' => 'P', 'slug' => 'p', 'status' => 'active',
        ]);
        $this->project->issueApiKey();

        $this->server = Server::create([
            'organization_id' => $this->org->id,
            'project_id' => $this->project->id,
            'name' => 'srv', 'hostname' => 'srv', 'os' => 'linux', 'status' => 'online',
        ]);

        $agent = Agent::create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'agent_id' => 'agt_test',
            'status' => 'active',
            'version' => '1.0.0',
        ]);
        $this->agentToken = $agent->issueToken();

        OrganizationContext::reset();
    }

    public function test_agent_can_report_a_threat(): void
    {
        $r = $this->postJson('/api/v1/agent/threats', [
            'type' => 'malware',
            'severity' => 'high',
            'title' => 'eval base64',
            'file_path' => '/var/www/x.php',
            'source_ip' => '1.2.3.4',
        ], ['Authorization' => 'Bearer '.$this->agentToken]);

        $r->assertCreated();

        OrganizationContext::asSystem();
        $this->assertSame(1, Threat::where('project_id', $this->project->id)->count());
        OrganizationContext::reset();
    }

    public function test_agent_endpoints_are_token_protected(): void
    {
        $this->postJson('/api/v1/agent/threats', [
            'type' => 'malware',
            'severity' => 'high',
            'title' => 'no auth',
        ], ['Authorization' => 'Bearer not-real'])->assertStatus(401);
    }
}
