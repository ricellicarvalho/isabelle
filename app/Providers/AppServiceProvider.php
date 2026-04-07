<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Receivable;
use App\Observers\ContractObserver;
use App\Observers\ReceivableObserver;
use Filament\Support\Enums\TextSize;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Contract::observe(ContractObserver::class);
        Receivable::observe(ReceivableObserver::class);

        TextColumn::configureUsing(function (TextColumn $column) {
			$column->size(TextSize::Medium);
		});

		FilamentColor::register([
			'brand' => '#1e084a',
			'red' => '#dc143c',
			'custom_color' => '#27DAF5',
		]);
    }
}
