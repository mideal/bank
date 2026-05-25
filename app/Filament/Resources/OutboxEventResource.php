<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboxEventResource\Pages;
use App\Models\OutboxEvent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OutboxEventResource extends Resource
{
    protected static ?string $model = OutboxEvent::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-inbox';
    }

    public static function getNavigationLabel(): string
    {
        return 'Outbox Events';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
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
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'processed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('attempts')->sortable(),
                TextColumn::make('error')->limit(50)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'processed' => 'Processed',
                    'failed' => 'Failed',
                ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutboxEvents::route('/'),
        ];
    }
}
