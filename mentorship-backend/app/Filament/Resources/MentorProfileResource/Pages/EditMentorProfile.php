<?php

namespace App\Filament\Resources\MentorProfileResource\Pages;

use App\Filament\Resources\MentorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMentorProfile extends EditRecord
{
    protected static string $resource = MentorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
