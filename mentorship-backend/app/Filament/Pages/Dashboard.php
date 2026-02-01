<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Register widgets explicitly
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\MentorshipChart::class,
            \App\Filament\Widgets\JobsChart::class,
            \App\Filament\Widgets\RecentActivities::class,
            \App\Filament\Widgets\LatestJobs::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 2; // 2 columns for charts
    }
}
