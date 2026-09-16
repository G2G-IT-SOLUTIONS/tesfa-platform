<?php

namespace App\Filament\Widgets;

use App\Models\DynamicPricingRecommendation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DynamicPricingMonitor extends BaseWidget
{
    protected static ?string $heading = 'Dynamic Pricing Recommendations';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DynamicPricingRecommendation::query()
                    ->where('host_action', 'pending')
                    ->where('expires_at', '>', now())
                    ->with('property')
                    ->orderBy('days_until_event')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->limit(30),
                Tables\Columns\TextColumn::make('current_rate')
                    ->money('ETB')
                    ->label('Current'),
                Tables\Columns\TextColumn::make('recommended_rate')
                    ->money('ETB')
                    ->label('Recommended'),
                Tables\Columns\TextColumn::make('rate_change_pct')
                    ->label('Change')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . number_format($state, 1) . '%')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('event_name')
                    ->label('Event')
                    ->default('—'),
                Tables\Columns\TextColumn::make('days_until_event')
                    ->label('Days')
                    ->default('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('apply')
                    ->label('Apply')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (DynamicPricingRecommendation $record) {
                        $record->update([
                            'host_action' => 'accepted',
                            'host_action_at' => now(),
                        ]);
                        // TODO: Apply to booking calendar
                    }),
            ]);
    }
}