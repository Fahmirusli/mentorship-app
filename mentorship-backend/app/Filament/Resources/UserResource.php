<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state)),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'mentor' => 'Mentor',
                                'mentee' => 'Mentee',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Profile Details')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('skills')
                            ->placeholder('Add skill')
                            ->separator(','),
                        Forms\Components\TagsInput::make('interests')
                            ->placeholder('Add interest')
                            ->separator(','),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'danger' => 'admin',
                        'success' => 'mentor',
                        'primary' => 'mentee',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->sortable()
                    ->label('Verified'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'mentor' => 'Mentor',
                        'mentee' => 'Mentee',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->label('Verify')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => !$record->is_verified)
                    ->action(function (User $record) {
                        $record->update(['is_verified' => true, 'verified_at' => now()]);
                        \Filament\Notifications\Notification::make()
                            ->title('User verified successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('unverify')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->label('Unverify')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => (bool) $record->is_verified)
                    ->action(function (User $record) {
                        $record->update(['is_verified' => false, 'verified_at' => null]);
                        \Filament\Notifications\Notification::make()
                            ->title('User unverified successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}