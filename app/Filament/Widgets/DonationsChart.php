<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;

class DonationsChart extends ChartWidget
{
    protected static ?string $heading = 'Донаты за последние 14 дней';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString());

        $sums = Donation::query()
            ->where('status', 'completed')
            ->where('paid_at', '>=', now()->subDays(13)->startOfDay())
            ->get()
            ->groupBy(fn (Donation $donation) => $donation->paid_at->toDateString())
            ->map(fn ($group) => $group->sum('amount_minor') / 100);

        return [
            'datasets' => [
                [
                    'label' => 'Сом',
                    'data' => $days->map(fn (string $day) => $sums->get($day, 0))->all(),
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $days->map(fn (string $day) => \Carbon\Carbon::parse($day)->format('d.m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
