<?php

namespace App\Filament\Widgets;

use App\Models\Recording;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DashboardStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $completed = Recording::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $processing = Recording::whereDate('created_at', $today)
            ->where('status', 'processing')
            ->count();

        $queued = Recording::whereDate('created_at', $today)
            ->where('status', 'queued')
            ->count();

        $failed = Recording::whereDate('created_at', $today)
            ->where('status', 'failed')
            ->count();

        $total = Recording::whereDate('created_at', $today)->count();

        return [
            Stat::make('Today\'s Recordings', $total)
                ->description('Total scanned today')
                ->icon('heroicon-o-video-camera'),

            Stat::make('Completed', $completed)
                ->description('Ready for history')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Processing/Queued', $processing . ' / ' . $queued)
                ->description('Currently in background')
                ->color('warning')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Failed', $failed)
                ->description('Requires attention')
                ->color($failed > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
