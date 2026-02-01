<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResource\Pages;
use App\Models\Feedback;
use App\Models\Mentorship;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Mentorship Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feedback Information')
                    ->schema([
                        Forms\Components\Select::make('mentorship_id')
                            ->label('Mentorship')
                            ->options(function () {
                                return Mentorship::with(['mentor', 'mentee'])
                                    ->get()
                                    ->mapWithKeys(function ($mentorship) {
                                        return [$mentorship->id => $mentorship->mentor->name . ' - ' . $mentorship->mentee->name];
                                    });
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('from_user_id')
                            ->label('From User')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('to_user_id')
                            ->label('To User')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('rating')
                            ->options([
                                1 => '⭐ (1 - Poor)',
                                2 => '⭐⭐ (2 - Fair)',
                                3 => '⭐⭐⭐ (3 - Good)',
                                4 => '⭐⭐⭐⭐ (4 - Very Good)',
                                5 => '⭐⭐⭐⭐⭐ (5 - Excellent)',
                            ])
                            ->required()
                            ->default(5),
                        Forms\Components\Textarea::make('comment')
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->placeholder('Write your feedback here...'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromUser.name')
                    ->label('From')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('toUser.name')
                    ->label('To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListFeedback::route('/'),
            'create' => Pages\CreateFeedback::route('/create'),
        ];
    }
}
