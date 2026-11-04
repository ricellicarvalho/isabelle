<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Colaborador;
use App\Models\User;
use App\Services\CollaboratorsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CollaboratorsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_collaborators_from_all_clients(): void
    {
        [$firstClient, $secondClient] = $this->clients();
        $this->collaborator($firstClient, 'Ana Souza', 'Financeiro');
        $this->collaborator($secondClient, 'Bruno Lima', 'Produção');

        $report = CollaboratorsReportService::generate([]);

        $this->assertSame(2, $report['count']);
        $this->assertSame(2, $report['clients_count']);
        $this->assertSame(['Bruno Lima', 'Ana Souza'], array_column($report['items'], 'nome'));
    }

    public function test_it_filters_collaborators_by_client(): void
    {
        [$firstClient, $secondClient] = $this->clients();
        $this->collaborator($firstClient, 'Ana Souza', 'Financeiro');
        $this->collaborator($secondClient, 'Bruno Lima', 'Produção');

        $report = CollaboratorsReportService::generate(['client_id' => $secondClient->id]);

        $this->assertSame(1, $report['count']);
        $this->assertSame('Bruno Lima', $report['items'][0]['nome']);
        $this->assertSame('Cliente Dois', $report['filters']['cliente']);
    }

    public function test_it_searches_name_phone_location_and_client(): void
    {
        [$firstClient, $secondClient] = $this->clients();
        $this->collaborator($firstClient, 'Ana Souza', 'Financeiro', '(63) 99999-0000');
        $this->collaborator($secondClient, 'Bruno Lima', 'Produção');

        foreach (['Ana', '99999', 'Financeiro', 'Cliente Um'] as $search) {
            $report = CollaboratorsReportService::generate(['search' => $search]);
            $this->assertSame(['Ana Souza'], array_column($report['items'], 'nome'));
        }
    }

    public function test_the_page_updates_the_list_when_a_client_is_selected(): void
    {
        [$firstClient, $secondClient] = $this->clients();
        $this->collaborator($firstClient, 'Ana Souza', 'Financeiro');
        $this->collaborator($secondClient, 'Bruno Lima', 'Produção');
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create([
            'name' => 'ViewAny:Client',
            'guard_name' => 'web',
        ]));

        Livewire::actingAs($user)
            ->test(\App\Filament\Pages\CollaboratorsReport::class)
            ->assertSee('Ana Souza')
            ->assertSee('Bruno Lima')
            ->set('data.client_id', $firstClient->id)
            ->assertSee('Ana Souza')
            ->assertDontSee('Bruno Lima');
    }

    public function test_it_generates_the_pdf_with_a_signed_authenticated_url(): void
    {
        [$client] = $this->clients();
        $this->collaborator($client, 'Ana Souza', 'Financeiro');

        $response = $this
            ->actingAs(User::factory()->create())
            ->get(URL::temporarySignedRoute(
                'reports.collaborators.pdf',
                now()->addMinute(),
                ['client_id' => $client->id],
            ));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="colaboradores-'.now()->format('Y-m-d').'.pdf"');
    }

    private function clients(): array
    {
        $user = User::factory()->create();

        return [
            Client::create([
                'tipo_pessoa' => 'pj',
                'cnpj_cpf' => '58.955.315/0001-72',
                'razao_social' => 'Cliente Um',
                'created_by' => $user->id,
            ]),
            Client::create([
                'tipo_pessoa' => 'pj',
                'cnpj_cpf' => '11.222.333/0001-81',
                'razao_social' => 'Cliente Dois',
                'created_by' => $user->id,
            ]),
        ];
    }

    private function collaborator(Client $client, string $name, string $location, ?string $phone = null): Colaborador
    {
        return Colaborador::create([
            'client_id' => $client->id,
            'nome' => $name,
            'telefone' => $phone,
            'local' => $location,
        ]);
    }
}
