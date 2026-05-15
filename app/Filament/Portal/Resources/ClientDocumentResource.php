<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ClientDocumentResource\Pages\ListClientDocuments;
use App\Models\Client;
use App\Models\ClientDocument;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;
use ZipArchive;

class ClientDocumentResource extends PortalResource
{
    protected static ?string $model = ClientDocument::class;

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Documentos';

    protected static ?int $navigationSort = 1;

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
                        'proposta'     => 'Proposta',
                        default        => 'Outro',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'laudo'        => 'info',
                        'relatorio'    => 'success',
                        'matriz_risco' => 'warning',
                        'certificado'  => 'purple',
                        'proposta'     => 'primary',
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
                Action::make('visualizar')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn (ClientDocument $record): string => 'Arquivos: ' . $record->titulo)
                    ->modalWidth('3xl')
                    ->modalCancelActionLabel('Fechar')
                    ->modalSubmitAction(false)
                    ->modalContent(function (ClientDocument $record): HtmlString {
                            $arquivos = array_values(array_filter((array) ($record->caminho_arquivo ?? [])));

                            if (empty($arquivos)) {
                                return new HtmlString('<p class="text-sm text-gray-500 p-4 text-center">Nenhum arquivo disponível.</p>');
                            }

                            $html = '<div class="space-y-5 p-1">';

                            foreach ($arquivos as $index => $path) {
                                if (! Storage::disk('local')->exists($path)) {
                                    continue;
                                }

                                $filename = basename($path);
                                $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                $url      = route('portal.document.file', [$record->id, $index]);

                                $html .= '<div class="border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden">';
                                $html .= '<div class="flex items-center justify-between bg-gray-50 dark:bg-white/5 px-4 py-2">';
                                $html .= '<span class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">' . e($filename) . '</span>';
                                $html .= '<a href="' . $url . '" target="_blank" class="text-xs text-blue-500 hover:underline whitespace-nowrap ml-2">Abrir em nova aba ↗</a>';
                                $html .= '</div>';
                                $html .= '<div class="p-3">';

                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $html .= '<img src="' . $url . '" class="max-w-full max-h-[480px] object-contain rounded mx-auto block" alt="' . e($filename) . '" loading="lazy" />';
                                } elseif ($ext === 'pdf') {
                                    $html .= '<iframe src="' . $url . '" class="w-full rounded border border-gray-200" style="height:520px;" title="' . e($filename) . '"></iframe>';
                                } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                                    $html .= '<video controls class="w-full rounded max-h-96">';
                                    $html .= '<source src="' . $url . '" />';
                                    $html .= '<p class="text-sm text-gray-500">Seu navegador não suporta reprodução de vídeo.</p>';
                                    $html .= '</video>';
                                } else {
                                    $html .= '<div class="flex flex-col items-center justify-center py-6 gap-3">';
                                    $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
                                    $html .= '<p class="text-sm text-gray-500">Visualização não disponível para este formato.</p>';
                                    $html .= '<a href="' . $url . '" target="_blank" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline font-medium">Abrir arquivo ↗</a>';
                                    $html .= '</div>';
                                }

                                $html .= '</div></div>';
                            }

                            $html .= '</div>';

                            return new HtmlString($html);
                        }),

                Action::make('download_all')
                    ->label('Baixar arquivo(s)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (ClientDocument $record) {
                        $arquivos = collect((array) ($record->caminho_arquivo ?? []))
                            ->filter(fn ($p) => Storage::disk('local')->exists($p))
                            ->values();

                        if ($arquivos->isEmpty()) {
                            return null;
                        }

                        if ($arquivos->count() === 1) {
                            $path = $arquivos->first();

                            return response()->streamDownload(
                                fn () => print(Storage::disk('local')->get($path)),
                                basename($path)
                            );
                        }

                        $zipPath = sys_get_temp_dir() . '/' . uniqid('docs_') . '.zip';
                        $zip     = new ZipArchive();
                        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                        foreach ($arquivos as $path) {
                            $zip->addFromString(basename($path), Storage::disk('local')->get($path));
                        }

                        $zip->close();

                        return response()
                            ->download($zipPath, Str::slug($record->titulo) . '.zip')
                            ->deleteFileAfterSend(true);
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientDocuments::route('/'),
        ];
    }
}
