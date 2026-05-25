<?php

namespace App\Filament\Resources;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrows-right-left';
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
                TextColumn::make('amount')->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (TransactionType $state): string => match ($state) {
                        TransactionType::Deposit => 'success',
                        TransactionType::Transfer => 'info',
                    })
                    ->formatStateUsing(fn (TransactionType $state): string => ucfirst($state->value)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::Completed => 'success',
                        TransactionStatus::Pending => 'warning',
                        TransactionStatus::Failed => 'danger',
                        TransactionStatus::Reversed => 'gray',
                    })
                    ->formatStateUsing(fn (TransactionStatus $state): string => ucfirst($state->value)),
                TextColumn::make('idempotency_key')->label('Idempotency Key')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'deposit' => 'Deposit',
                    'transfer' => 'Transfer',
                ]),
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'reversed' => 'Reversed',
                ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}
