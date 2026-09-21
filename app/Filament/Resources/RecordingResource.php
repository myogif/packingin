<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordingResource\Pages;
use App\Models\Recording;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecordingResource extends Resource
{
    protected static ?string $model = Recording::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Recordings History';
    protected static ?string $pluralModelLabel = 'Recordings';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('resi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('platform')
                    ->options([
                        'shopee' => 'Shopee',
                        'tiktok' => 'TikTok Shop',
                        'tokopedia' => 'Tokopedia',
                        'lazada' => 'Lazada',
                        'blibli' => 'Blibli',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->numeric()
                    ->suffix('seconds'),
                Forms\Components\TextInput::make('file_size')
                    ->numeric()
                    ->suffix('bytes'),
                Forms\Components\DateTimePicker::make('recorded_at'),
                Forms\Components\DateTimePicker::make('processed_at'),
                Forms\Components\Textarea::make('error_message')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : '-')
                    ->label('Duration')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 2) . ' MB' : '-')
                    ->label('Size')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'shopee' => 'Shopee',
                        'tiktok' => 'TikTok Shop',
                        'tokopedia' => 'Tokopedia',
                        'lazada' => 'Lazada',
                        'blibli' => 'Blibli',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\Filter::make('recorded_at')
                    ->form([
                        Forms\Components\DatePicker::make('recorded_from'),
                        Forms\Components\DatePicker::make('recorded_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['recorded_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('recorded_at', '>=', $date),
                            )
                            ->when(
                                $data['recorded_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('recorded_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('play')
                    ->label('Play Video')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Recording $record): bool => $record->status === 'completed' && $record->final_path)
                    ->url(fn (Recording $record): string => route('recordings.play', $record)),

                Tables\Actions\Action::make('retry')
                    ->label('Retry Processing')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Recording $record): bool => $record->status === 'failed')
                    ->action(function (Recording $record) {
                        $record->update(['status' => 'queued', 'error_message' => null]);
                        // Will trigger job via Service later
                        \App\Jobs\ProcessRecordingJob::dispatch($record);
                        Filament\Notifications\Notification::make()
                            ->title('Processing retried')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordings::route('/'),
            // 'create' => Pages\CreateRecording::route('/create'),
            'view' => Pages\ViewRecording::route('/{record}'),
        ];
    }
}
