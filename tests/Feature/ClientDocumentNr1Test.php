<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Nr1Cycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClientDocumentNr1Test extends TestCase
{
    use RefreshDatabase;

    public function test_devolutiva_is_linked_to_the_clients_nr1_cycle(): void
    {
        [$user, $client, $cycle] = $this->dependencies();

        $document = ClientDocument::create([
            'client_id' => $client->id,
            'nr1_cycle_id' => $cycle->id,
            'titulo' => 'Devolutiva NR-1/2026',
            'tipo' => 'devolutiva_nr1',
            'caminho_arquivo' => ['documentos-clientes/devolutiva.pdf'],
            'visivel_portal' => true,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($document->nr1Cycle->is($cycle));
        $this->assertTrue($cycle->devolutivas()->first()->is($document));
    }

    public function test_devolutiva_cannot_use_another_clients_cycle(): void
    {
        [$user, $client] = $this->dependencies();
        $otherClient = Client::create([
            'cnpj_cpf' => '11.222.333/0001-44',
            'razao_social' => 'Outro cliente',
            'created_by' => $user->id,
        ]);
        $otherCycle = Nr1Cycle::create([
            'client_id' => $otherClient->id,
            'reference_year' => 2026,
        ]);

        $this->expectException(ValidationException::class);

        ClientDocument::create([
            'client_id' => $client->id,
            'nr1_cycle_id' => $otherCycle->id,
            'titulo' => 'Devolutiva inválida',
            'tipo' => 'devolutiva_nr1',
            'caminho_arquivo' => ['documentos-clientes/devolutiva.pdf'],
            'visivel_portal' => true,
            'created_by' => $user->id,
        ]);
    }

    public function test_non_nr1_document_does_not_keep_a_cycle_link(): void
    {
        [$user, $client, $cycle] = $this->dependencies();

        $document = ClientDocument::create([
            'client_id' => $client->id,
            'nr1_cycle_id' => $cycle->id,
            'titulo' => 'Relatório comum',
            'tipo' => 'relatorio',
            'caminho_arquivo' => ['documentos-clientes/relatorio.pdf'],
            'visivel_portal' => true,
            'created_by' => $user->id,
        ]);

        $this->assertNull($document->nr1_cycle_id);
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $client = Client::create([
            'cnpj_cpf' => '98.765.432/0001-10',
            'razao_social' => 'Cliente NR-1',
            'created_by' => $user->id,
        ]);
        $cycle = Nr1Cycle::create([
            'client_id' => $client->id,
            'reference_year' => 2026,
        ]);

        return [$user, $client, $cycle];
    }
}
