<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Users';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(fn (Account $record): string => $record->balance->amount),
                TextColumn::make('hold')
                    ->label('Hold')
                    ->getStateUsing(fn (Account $record): string => $record->hold->amount),
                TextColumn::make('available')
                    ->label('Available')
                    ->getStateUsing(fn (Account $record): string => $record->available()->amount),
                TextColumn::make('currency')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
        ];
    }
}
