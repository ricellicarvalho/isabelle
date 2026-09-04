<?php

namespace App\Filament\Resources\BankMovements\Pages;
use App\Filament\Resources\BankMovements\BankMovementResource;
use Filament\Resources\Pages\CreateRecord;
class CreateBankMovement extends CreateRecord { protected static string $resource = BankMovementResource::class; protected function mutateFormDataBeforeCreate(array $data): array { return $data + ['origin' => 'manual', 'status' => 'confirmed', 'created_by' => auth()->id()]; } }
