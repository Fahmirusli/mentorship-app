<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Mentorship;
use App\Models\Job;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    // Add polling to refresh data every 30 seconds instead of on every load
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        // Cache the stats for 5 minutes
        return Cache::remember('dashboard-stats', now()->addMinutes(5), function () {
            return [
                Stat::make('Total Users', User::count())
                    ->description('All registered users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('success'),
                    
                Stat::make('Total Mentors', User::where('role', 'mentor')->count())
                    ->description('Active mentors')
                    ->descriptionIcon('heroicon-m-academic-cap')
                    ->color('primary'),
                    
                Stat::make('Total Mentees', User::where('role', 'mentee')->count())
                    ->description('Active mentees')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('info'),
                    
                Stat::make('Active Mentorships', Mentorship::where('status', 'active')->count())
                    ->description('Currently active')
                    ->descriptionIcon('heroicon-m-heart')
                    ->color('warning'),
                    
                Stat::make('Active Jobs', Job::where('is_active', true)->count())
                    ->description('Available jobs')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('success'),
                    
                Stat::make('Upcoming Appointments', 
                    Appointment::where('scheduled_at', '>', now())
                        ->where('status', 'scheduled')
                        ->count()
                )
                    ->description('Scheduled sessions')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('danger'),
            ];
        });
    }
}