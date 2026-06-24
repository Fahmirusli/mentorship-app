<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            
            // ============================================
            // FIXED: Better Dark Theme Colors
            // ============================================
            ->colors([
                'primary' => Color::Purple,      // More vibrant
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'info' => Color::Cyan,
                'gray' => Color::Zinc,          // Better for dark mode
            ])
            
            // ============================================
            // FIXED: Proper Dark Mode
            // ============================================
            ->darkMode()  // Remove 'true' - let it auto-detect
            
            // ============================================
            // ADDED: Brand and Navigation
            // ============================================
            ->brandName('🎓 Mentorship Hub')
            
            // ============================================
            // ADDED: Better navigation grouping
            // ============================================
            ->navigationGroups([
                'User Management',
                'Mentorship Management',
                'Job Management',
            ])
            
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')

            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\MentorshipChart::class,
                \App\Filament\Widgets\JobsChart::class,
                \App\Filament\Widgets\RecentActivities::class,
                \App\Filament\Widgets\LatestJobs::class,
            ])
              
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

    }
}