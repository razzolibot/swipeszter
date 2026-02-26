<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon  = '👤';
    protected static ?string $navigationLabel = 'Felhasználók';
    protected static ?string $modelLabel      = 'Felhasználó';
    protected static ?int $navigationSort     = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Név')->required(),
            TextInput::make('username')->label('Felhasználónév')->required(),
            TextInput::make('email')->label('Email')->email()->required(),
            Textarea::make('bio')->label('Bio')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Név')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->label('@Felhasználónév')
                    ->searchable()
                    ->prefix('@'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('videos_count')
                    ->label('Videók')
                    ->counts('videos')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Regisztráció')
                    ->dateTime('Y.m.d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('today')
                    ->label('Ma regisztrált')
                    ->query(fn (Builder $q) => $q->whereDate('created_at', today())),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view'  => Pages\ViewUser::route('/{record}'),
        ];
    }
}
