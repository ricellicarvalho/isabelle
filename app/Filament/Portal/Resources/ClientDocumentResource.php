<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ClientDocumentResource\Pages\ListClientDocuments;
use App\Models\Client;
use App\Models\ClientDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ClientDocumentResource extends Resource
{
    protected static ?string $model = ClientDocument::class;

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $client = Client::where('portal_user_id', Auth::id())->first();

        return parent::getEloquentQuery()
            ->where('client_id', $client?->id)
            ->where('visivel_portal', true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'laudo'        => 'Laudo',
                        'foto'         => 'Foto',
                        'relatorio'    => 'Relatório',
                        'matriz_risco' => 'Matriz de Risco',
                        'certificado'  => 'Certificado',
                        default        => 'Outro',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'laudo'        => 'info',
                        'relatorio'    => 'success',
                        'matriz_risco' => 'warning',
                        'certificado'  => 'purple',
                        default        => 'gray',
                    }),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Disponibilizado em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('download_all')
                        ->label('Baixar arquivos')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (ClientDocument $record) {
                            $arquivos = (array) ($record->caminho_arquivo ?? []);
                            $primeiro = collect($arquivos)->first(fn ($p) => Storage::disk('local')->exists($p));

                            if (! $primeiro) {
                                return null;
                            }

                            return response()->streamDownload(
                                fn () => print(Storage::disk('local')->get($primeiro)),
                                basename($primeiro)
                            );
                        }),
                ])
                    ->label('Ações')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button(),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientDocuments::route('/'),
        ];
    }
}
