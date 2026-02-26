<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('👤 Felhasználók', User::count())
                ->description(User::whereDate('created_at', today())->count() . ' ma regisztrált')
                ->color('success'),

            Stat::make('🎬 Videók', Video::count())
                ->description(Video::where('status', 'ready')->count() . ' elérhető')
                ->color('info'),

            Stat::make('⏳ Feldolgozás alatt', Video::where('status', 'processing')->count())
                ->description(Video::where('status', 'failed')->count() . ' sikertelen')
                ->color('warning'),

            Stat::make('💬 Kommentek', Comment::count())
                ->description(Comment::whereDate('created_at', today())->count() . ' ma')
                ->color('primary'),

            Stat::make('👁️ Megtekintések', Video::sum('views_count'))
                ->description('összes videó megtekintés')
                ->color('success'),

            Stat::make('❤️ Lájkok', Video::sum('likes_count'))
                ->description('összes lájk')
                ->color('danger'),
        ];
    }
}
