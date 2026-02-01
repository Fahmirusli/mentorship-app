<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Job;

class JobsChart extends ChartWidget
{
    protected static ?string $heading = 'Jobs by Platform';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Jobs',
                    'data' => [
                        Job::where('source_platform', 'JobStreet')->count(),
                        Job::where('source_platform', 'LinkedIn')->count(),
                        Job::where('source_platform', 'Hiredly')->count(),
                    ],
                    'backgroundColor' => [
                        'rgb(168, 85, 247)',  // Purple
                        'rgb(236, 72, 153)',  // Pink
                        'rgb(59, 130, 246)',  // Blue
                        'rgb(34, 197, 94)',   // Green
                    ],
                ],
            ],
            'labels' => ['JobStreet', 'LinkedIn', 'Hiredly'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}