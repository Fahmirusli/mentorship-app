<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Mentorship;

class RecentActivities extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Mentorship::query()
                    ->with(['mentor', 'mentee'])
                    ->latest()
                    ->limit(5)
            )
            ->heading('Recent Mentorship Activities')
            ->columns([
                Tables\Columns\TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mentee.name')
                    ->label('Mentee')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A'),
            ]);
    }
}