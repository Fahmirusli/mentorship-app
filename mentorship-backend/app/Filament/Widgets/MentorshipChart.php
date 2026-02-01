<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Mentorship;

class MentorshipChart extends ChartWidget
{
    protected static ?string $heading = 'Mentorships Overview';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Mentorships',
                    'data' => [
                        Mentorship::where('status', 'pending')->count(),
                        Mentorship::where('status', 'active')->count(),
                        Mentorship::where('status', 'completed')->count(),
                        Mentorship::where('status', 'cancelled')->count(),
                    ],
                    'backgroundColor' => [
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                    ],
                ],
            ],
            'labels' => ['Pending', 'Active', 'Completed', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}