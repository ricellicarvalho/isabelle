<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Receivables\ReceivableResource;
use App\Models\Receivable;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class OverdueReceivablesTable extends BaseWidget
{
    protected static ?string $heading = 'Parcelas Vencidas';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('View:OverdueReceivablesTable') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Receivable::query()
                    ->whereIn('status', ['pendente', 'vencido'])
                    ->whereDate('data_vencimento', '<', now())
                    ->orderBy('data_vencimento', 'asc')
            )
            ->paginated(false)
            ->recordTitleAttribute('descricao')
            ->columns([
                TextColumn::make('client.razao_social')->label('Cliente')->limit(30)->placeholder('—'),
                TextColumn::make('descricao')->label('Descrição')->limit(35),
                TextColumn::make('valor')->label('Valor')->money('BRL'),
                TextColumn::make('data_vencimento')->label('Vencimento')->date('d/m/Y'),
                TextColumn::make('dias_atraso')
                    ->label('Atraso')
                    ->state(function ($record): string {
                        $dias = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($record->data_vencimento)->startOfDay(), false);

                        return abs((int) $dias) . ' dias';
                    })
                    ->badge()
                    ->color('danger'),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Receivable $record): string => ReceivableResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
