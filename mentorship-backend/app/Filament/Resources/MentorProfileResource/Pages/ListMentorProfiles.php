<?php

namespace App\Filament\Resources\MentorProfileResource\Pages;

use App\Filament\Resources\MentorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMentorProfiles extends ListRecords
{
    protected static string $resource = MentorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
