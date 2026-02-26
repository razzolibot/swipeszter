<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;
    protected static ?string $navigationIcon  = '🎬';
    protected static ?string $navigationLabel = 'Videók';
    protected static ?string $modelLabel      = 'Videó';
    protected static ?int $navigationSort     = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->height(60)
                    ->width(40),
                TextColumn::make('title')
                    ->label('Cím')
                    ->searchable()
                    ->limit(40)
                    ->default('(nincs cím)'),
                TextColumn::make('user.username')
                    ->label('Feltöltő')
                    ->searchable()
                    ->prefix('@'),
                TextColumn::make('status')
                    ->label('Státusz')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready'      => 'success',
                        'processing' => 'warning',
                        'pending'    => 'info',
                        'failed'     => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('views_count')
                    ->label('Megtekintés')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('likes_count')
                    ->label('Lájk')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Hossz')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '—'),
                TextColumn::make('created_at')
                    ->label('Feltöltve')
                    ->dateTime('Y.m.d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Státusz')
                    ->options([
                        'pending'    => 'Várakozik',
                        'processing' => 'Feldolgozás',
                        'ready'      => 'Kész',
                        'failed'     => 'Sikertelen',
                    ]),
            ])
            ->actions([
                Action::make('toggle_visibility')
                    ->label(fn (Video $r) => $r->is_public ? '🔒 Elrejt' : '👁️ Megjelenít')
                    ->action(fn (Video $r) => $r->update(['is_public' => !$r->is_public]))
                    ->color(fn (Video $r) => $r->is_public ? 'warning' : 'success'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
        ];
    }
}
