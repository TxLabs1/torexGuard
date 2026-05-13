<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $user;
    private Project $project;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org     = Organization::create(['name' => 'Acme', 'slug' => 'acme']);
        $this->user    = User::factory()->create();
        $this->org->users()->attach($this->user->id, ['role' => 'admin']);
        $this->project = Project::create([
            'organization_id' => $this->org->id,
            'user_id'         => $this->user->id,
            'name'            => 'Demo',
            'slug'            => 'demo',
            'status'          => 'active',
        ]);
        $this->apiKey = $this->project->issueApiKey()['api_key'];
    }

    public function test_agent_can_register_with_valid_api_key(): void
    {
        $response = $this->postJson('/api/v1/agent/register', [
            'project_api_key' => $this->apiKey,
            'hostname'        => 'web-01',
            'os'              => 'linux',
            'version'         => '1.0.0',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['agent_id', 'agent_token', 'server_id', 'config']);

        $this->assertDatabaseHas('servers', ['hostname' => 'web-01']);
        $this->assertDatabaseHas('agents', ['project_id' => $this->project->id]);
        $this->assertNotEmpty($response->json('agent_token'));
    }

    public function test_agent_registration_rejects_invalid_api_key(): void
    {
        $this->postJson('/api/v1/agent/register', [
            'project_api_key' => 'tk_thisisnotvalid_00000000',
            'hostname'        => 'web-02',
            'os'              => 'linux',
            'version'         => '1.0.0',
        ])->assertStatus(401);
    }

    public function test_authenticated_agent_can_heartbeat_but_unauthenticated_cannot(): void
    {
        $reg = $this->postJson('/api/v1/agent/register', [
            'project_api_key' => $this->apiKey,
            'hostname'        => 'web-03',
            'os'              => 'linux',
            'version'         => '1.0.0',
        ])->assertOk();

        $token = $reg->json('agent_token');

        $this->postJson('/api/v1/agent/heartbeat', [
            'cpu_usage' => 5, 'memory_usage' => 10, 'disk_usage' => 50,
        ], ['Authorization' => 'Bearer '.$token])->assertSuccessful();

        $this->postJson('/api/v1/agent/heartbeat', [], [
            'Authorization' => 'Bearer invalid-token-here',
        ])->assertStatus(401);
    }

    public function test_agent_register_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/agent/register', [
                'project_api_key' => 'tk_nope',
                'hostname'        => 'h',
                'os'              => 'linux',
                'version'         => '1.0.0',
            ]);
        }

        $this->postJson('/api/v1/agent/register', [
            'project_api_key' => 'tk_nope',
            'hostname'        => 'h',
            'os'              => 'linux',
            'version'         => '1.0.0',
        ])->assertStatus(429);
    }
}
