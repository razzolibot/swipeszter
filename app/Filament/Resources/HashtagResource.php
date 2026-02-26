<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HashtagResource\Pages;
use App\Models\Hashtag;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HashtagResource extends Resource
{
    protected static ?string $model = Hashtag::class;
    protected static ?string $navigationIcon  = '#️⃣';
    protected static ?string $navigationLabel = 'Hashtagek';
    protected static ?string $modelLabel      = 'Hashtag';
    protected static ?int $navigationSort     = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Hashtag')
                    ->prefix('#')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray'),
                TextColumn::make('videos_count')
                    ->label('Videók száma')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Létrehozva')
                    ->dateTime('Y.m.d')
                    ->sortable(),
            ])
            ->defaultSort('videos_count', 'desc')
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHashtags::route('/'),
        ];
    }
}
