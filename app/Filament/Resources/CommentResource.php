<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
    protected static ?string $navigationIcon  = '💬';
    protected static ?string $navigationLabel = 'Kommentek';
    protected static ?string $modelLabel      = 'Komment';
    protected static ?int $navigationSort     = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('Felhasználó')
                    ->prefix('@')
                    ->searchable(),
                TextColumn::make('content')
                    ->label('Tartalom')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('video.title')
                    ->label('Videó')
                    ->limit(30)
                    ->default('(nincs cím)'),
                TextColumn::make('created_at')
                    ->label('Dátum')
                    ->dateTime('Y.m.d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListComments::route('/'),
        ];
    }
}
