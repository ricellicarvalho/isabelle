<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Support\PortalAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalMultiClientAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_selected_portal_client_from_the_session(): void
    {
        $admin = User::factory()->create();
        $portalUser = User::factory()->create(['email' => 'admsantaangelina@gmail.com']);

        $clientA = $this->client($admin, [
            'razao_social' => 'Fazenda A',
            'cnpj_cpf' => '01.122.629/0001-09',
            'portal_user_id' => $portalUser->id,
        ]);

        $clientB = $this->client($admin, [
            'razao_social' => 'Fazenda B',
            'cnpj_cpf' => '01.912.109/0001-90',
            'portal_user_id' => $portalUser->id,
        ]);

        $this->withSession([PortalAccess::SESSION_CLIENT_ID => $clientB->id]);

        $this->assertSame([$clientA->id, $clientB->id], PortalAccess::clients($portalUser->id)->pluck('id')->sort()->values()->all());
        $this->assertSame($clientB->id, PortalAccess::client($portalUser->id)?->id);

        $this->assertTrue(PortalAccess::selectClient($portalUser->id, $clientA->id));
        $this->assertSame($clientA->id, PortalAccess::client($portalUser->id)?->id);
    }

    public function test_it_rejects_switching_to_a_client_not_linked_to_the_user(): void
    {
        $admin = User::factory()->create();
        $portalUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $allowedClient = $this->client($admin, ['portal_user_id' => $portalUser->id]);
        $blockedClient = $this->client($admin, ['portal_user_id' => $otherUser->id]);

        $this->withSession([PortalAccess::SESSION_CLIENT_ID => $allowedClient->id]);

        $this->assertFalse(PortalAccess::selectClient($portalUser->id, $blockedClient->id));
        $this->assertSame($allowedClient->id, PortalAccess::client($portalUser->id)?->id);
    }

    public function test_the_portal_switch_route_stores_only_authorized_clients(): void
    {
        $admin = User::factory()->create();
        $portalUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $allowedClient = $this->client($admin, ['portal_user_id' => $portalUser->id]);
        $blockedClient = $this->client($admin, ['portal_user_id' => $otherUser->id]);

        $this->actingAs($portalUser, 'portal')
            ->post(route('portal.select-client'), ['client_id' => $allowedClient->id])
            ->assertRedirect();

        $this->assertSame($allowedClient->id, session(PortalAccess::SESSION_CLIENT_ID));

        $this->actingAs($portalUser, 'portal')
            ->post(route('portal.select-client'), ['client_id' => $blockedClient->id])
            ->assertForbidden();

        $this->assertSame($allowedClient->id, session(PortalAccess::SESSION_CLIENT_ID));
    }

    private function client(User $admin, array $attributes = []): Client
    {
        return Client::create(array_merge([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => fake()->unique()->numerify('##.###.###/####-##'),
            'razao_social' => fake()->company(),
            'email' => fake()->safeEmail(),
            'contato_nome' => 'Wenya',
            'nr1_status' => 'pendente',
            'status' => 'ativo',
            'created_by' => $admin->id,
        ], $attributes));
    }
}
