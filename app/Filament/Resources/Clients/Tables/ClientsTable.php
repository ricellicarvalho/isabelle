<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cnpj_cpf')
                    ->label('CNPJ/CPF')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('razao_social')
                    ->label('Razão Social')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('nome_fantasia')
                    ->label('Nome Fantasia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('telefone_principal')
                    ->label('Telefone')
                    ->state(function (Client $record): string {
                        $tel = collect($record->telefones ?? [])->first();
                        if (! $tel || empty($tel['numero'])) {
                            return $record->telefone ?? '—';
                        }
                        $d = preg_replace('/\D/', '', $tel['numero']);

                        return match (strlen($d)) {
                            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $d),
                            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $d),
                            default => $tel['numero'],
                        };
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $query->where('telefones', 'like', "%{$search}%");
                    }),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cidade')
                    ->label('Cidade')
                    ->sortable(),

                TextColumn::make('uf')
                    ->label('UF')
                    ->sortable(),

                TextColumn::make('cadastro_preenchido')
                    ->label('Pré-Cadastro')
                    ->badge()
                    ->color(fn ($record): string => $record->cadastro_preenchido ? 'success' : 'warning')
                    ->formatStateUsing(fn ($record): string => $record->cadastro_preenchido ? 'Preenchido' : 'Pendente')
                    ->sortable(),

                TextColumn::make('nr1_checklist_progresso')
                    ->label('Checklist NR-1')
                    ->state(fn (Client $record): string => $record->nr1ChecklistProgresso() . '%')
                    ->badge()
                    ->color(fn (Client $record): string => match (true) {
                        $record->nr1ChecklistProgresso() === 100 => 'success',
                        $record->nr1ChecklistProgresso() > 0     => 'warning',
                        default                                  => 'danger',
                    })
                    ->sortable(false),

                TextColumn::make('nr1_status')
                    ->label('NR-1')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente'     => 'danger',
                        'em_andamento' => 'warning',
                        'regularizada' => 'success',
                        'finalizada'   => 'info',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente'     => 'Pendente',
                        'em_andamento' => 'Em Andamento',
                        'regularizada' => 'Regularizada',
                        'finalizada'   => 'Finalizada',
                        default        => $state,
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'inativo' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo',
                    }),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('cadastro_preenchido')
                    ->label('Pré-Cadastro')
                    ->options([
                        '1' => 'Preenchido',
                        '0' => 'Pendente',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo',
                    ]),

                SelectFilter::make('nr1_status')
                    ->label('Status NR-1')
                    ->options([
                        'pendente' => 'Pendente',
                        'em_andamento' => 'Em Andamento',
                        'regularizada' => 'Regularizada',
                        'finalizada' => 'Finalizada',
                    ]),

                SelectFilter::make('uf')
                    ->label('UF')
                    ->options([
                        'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM',
                        'BA' => 'BA', 'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES',
                        'GO' => 'GO', 'MA' => 'MA', 'MT' => 'MT', 'MS' => 'MS',
                        'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB', 'PR' => 'PR',
                        'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
                        'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC',
                        'SP' => 'SP', 'SE' => 'SE', 'TO' => 'TO',
                    ])
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
