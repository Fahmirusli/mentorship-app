<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('All Jobs'),
            'active' => \Filament\Resources\Components\Tab::make('Active Jobs')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'inactive' => \Filament\Resources\Components\Tab::make('Inactive Jobs')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false)),
        ];
    }
}
