<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
