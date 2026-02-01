<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Job;

class LatestJobs extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Job::query()
                    ->where('is_active', true)
                    ->latest()
                    ->limit(5)
            )
            ->heading('Latest Job Postings')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('company')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('source_platform')
                    ->colors([
                        'primary' => 'JobStreet',
                        'success' => 'LinkedIn',
                        'warning' => 'Hiredly',
                    ]),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('posted_date')
                    ->date('M d, Y'),
            ]);
    }
}