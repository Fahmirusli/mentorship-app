<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Job Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('required_skills')
                            ->placeholder('Add skill')
                            ->separator(',')
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Job Details')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255),
                        Forms\Components\Select::make('job_type')
                            ->options([
                                'Full-time' => 'Full-time',
                                'Part-time' => 'Part-time',
                                'Contract' => 'Contract',
                                'Internship' => 'Internship',
                            ]),
                        Forms\Components\TextInput::make('experience_level')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('salary_range')
                            ->maxLength(255),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Source Information')
                    ->schema([
                        Forms\Components\Select::make('source_platform')
                            ->options([
                                'JobStreet' => 'JobStreet',
                                'LinkedIn' => 'LinkedIn',
                                'Hiredly' => 'Hiredly',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('source_url')
                            ->url()
                            ->required(),
                        Forms\Components\DatePicker::make('posted_date'),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('source_platform')
                    ->colors([
                        'primary' => 'JobStreet',
                        'success' => 'LinkedIn',
                        'warning' => 'Hiredly',
                    ]),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('job_type')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_platform')
                    ->options([
                        'JobStreet' => 'JobStreet',
                        'LinkedIn' => 'LinkedIn',
                        'Hiredly' => 'Hiredly',
                    ]),
                Tables\Filters\SelectFilter::make('job_type'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
            'index' => Pages\ListJobs::route('/'),
            'create' => Pages\CreateJob::route('/create'),
            'edit' => Pages\EditJob::route('/{record}/edit'),
        ];
    }
}
