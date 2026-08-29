<?php

namespace App\Filament\Widgets;

use App\Models\CaseRequest;
use App\Models\Disbursement;
use App\Models\Donation;
use App\Models\FundCase;
use App\Models\PublicIntake;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalDonated = Donation::query()->where('status', 'completed')->sum('amount_minor');
        $totalDisbursed = Disbursement::query()->sum('amount_minor');
        $activeCases = FundCase::query()->where('status', 'active')->count();
        $pendingRequests = CaseRequest::query()->where('status', 'pending')->count();
        $newIntakes = PublicIntake::query()->where('status', 'new')->count();

        return [
            Stat::make('Собрано всего', number_format($totalDonated / 100, 0, '.', ' ').' сом')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Выплачено', number_format($totalDisbursed / 100, 0, '.', ' ').' сом')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('warning'),
            Stat::make('Активных кейсов', $activeCases)
                ->icon('heroicon-o-briefcase'),
            Stat::make('Заявок на рассмотрении', $pendingRequests)
                ->icon('heroicon-o-inbox-stack')
                ->color($pendingRequests > 0 ? 'warning' : 'gray'),
            Stat::make('Новых заявок с сайта', $newIntakes)
                ->icon('heroicon-o-globe-alt')
                ->color($newIntakes > 0 ? 'warning' : 'gray'),
        ];
    }
}
