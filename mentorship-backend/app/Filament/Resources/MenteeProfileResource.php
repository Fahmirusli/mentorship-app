<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenteeProfileResource\Pages;
use App\Models\MenteeProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenteeProfileResource extends Resource
{
    protected static ?string $model = MenteeProfile::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mentee Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Mentee User')
                            ->options(User::where('role', 'mentee')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('education_level')
                            ->required()
                            ->maxLength(255)
                            ->placeholder("Bachelor's Degree, Master's, etc."),
                        Forms\Components\TextInput::make('field_of_study')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Computer Science, Business, etc.'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Skills & Goals')
                    ->schema([
                        Forms\Components\TagsInput::make('current_skills')
                            ->placeholder('Add skill')
                            ->separator(',')
                            ->required(),
                        Forms\Components\TagsInput::make('skills_to_learn')
                            ->placeholder('Add skill to learn')
                            ->separator(',')
                            ->required(),
                        Forms\Components\Textarea::make('career_goals')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->placeholder('Describe your career goals and aspirations...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Mentee Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('education_level')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('field_of_study')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TagsColumn::make('current_skills')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TagsColumn::make('skills_to_learn')
                    ->label('Want to Learn')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('education_level')
                    ->options([
                        "Bachelor's Degree" => "Bachelor's Degree",
                        "Master's Degree" => "Master's Degree",
                        'Diploma' => 'Diploma',
                        'PhD' => 'PhD',
                    ]),
            ])
            ->actions([
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
            'index' => Pages\ListMenteeProfiles::route('/'),
            'create' => Pages\CreateMenteeProfile::route('/create'),
            'edit' => Pages\EditMenteeProfile::route('/{record}/edit'),
        ];
    }
}