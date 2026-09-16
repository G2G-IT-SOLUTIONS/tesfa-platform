<?php

namespace App\Filament\Widgets;

use App\Models\FraudAlert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FraudDetectionCenter extends BaseWidget
{
    protected static ?string $heading = 'Fraud Detection Center';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FraudAlert::query()
                    ->whereIn('status', ['new', 'under_review'])
                    ->orderBy('confidence_score', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('alert_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'duplicate_listing' => 'warning',
                        'price_anomaly' => 'warning',
                        'fake_document' => 'danger',
                        'agent_collusion' => 'danger',
                        'identity_theft' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'success',
                    }),
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target'),
                Tables\Columns\TextColumn::make('target_id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->color(fn ($state) => $state > 0.8 ? 'danger' : ($state > 0.5 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Detected')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->url(fn (FraudAlert $record) => route('filament.admin.resources.fraud-alerts.edit', $record)),
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (FraudAlert $record) => $record->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                    ]))
                    ->requiresConfirmation(),
            ]);
    }
}