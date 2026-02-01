<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentorProfileResource\Pages;
use App\Models\MentorProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MentorProfileResource extends Resource
{
    protected static ?string $model = MentorProfile::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mentor Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Mentor User')
                            ->options(User::where('role', 'mentor')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TagsInput::make('expertise_areas')
                            ->placeholder('Add expertise')
                            ->separator(',')
                            ->required(),
                        Forms\Components\TextInput::make('industry')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('job_title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('years_of_experience')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix('years'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Mentorship Details')
                    ->schema([
                        Forms\Components\Textarea::make('mentorship_approach')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->placeholder('Describe your mentorship approach...'),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Available for Mentorship')
                            ->required()
                            ->default(true),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.01)
                            ->default(0.00)
                            ->suffix('/ 5.00'),
                        Forms\Components\TextInput::make('total_mentees')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('mentees'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Mentor Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('industry')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('years_of_experience')
                    ->label('Experience')
                    ->suffix(' yrs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' ⭐')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_mentees')
                    ->label('Mentees')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Availability'),
                Tables\Filters\Filter::make('highly_rated')
                    ->query(fn ($query) => $query->where('rating', '>=', 4.5))
                    ->label('Highly Rated (4.5+)'),
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
            'index' => Pages\ListMentorProfiles::route('/'),
            'create' => Pages\CreateMentorProfile::route('/create'),
            'edit' => Pages\EditMentorProfile::route('/{record}/edit'),
        ];
    }
}