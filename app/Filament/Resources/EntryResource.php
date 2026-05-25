<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntryResource\Pages;
use App\Models\Entry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntryResource extends Resource
{
    protected static ?string $model = Entry::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationLabel(): string
    {
        return 'Ledger Entries';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
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
                TextColumn::make('transaction_id')->sortable(),
                TextColumn::make('account_id')->sortable(),
                TextColumn::make('amount')
                    ->sortable()
                    ->color(fn (string $state): string => str_starts_with($state, '-') ? 'danger' : 'success'),
                TextColumn::make('balance_after')->label('Balance After')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntries::route('/'),
        ];
    }
}
