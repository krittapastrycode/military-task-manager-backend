<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProtectionPersonResource\Pages;
use App\Models\ProtectionPerson;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProtectionPersonResource extends Resource
{
    protected static ?string $model = ProtectionPerson::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'รายชื่อบุคคลอารักขา';

    protected static ?string $modelLabel = 'บุคคลอารักขา';

    protected static ?string $pluralModelLabel = 'รายชื่อบุคคลอารักขา';

    protected static ?string $navigationGroup = 'ผู้ใช้งาน / ผู้ดูแล';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('category')
                ->label('หมวดหมู่')
                ->options([
                    'royal' => 'พระบรมวงศานุวงศ์',
                    'vip'   => 'บุคคลสำคัญ',
                ])
                ->required()
                ->native(false),

            TextInput::make('name')
                ->label('ชื่อ')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('order')
                ->label('ลำดับ')
                ->numeric()
                ->default(0)
                ->minValue(0),

            Toggle::make('is_active')
                ->label('เปิดใช้งาน')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label('หมวดหมู่')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'royal' ? 'พระบรมวงศานุวงศ์' : 'บุคคลสำคัญ')
                    ->color(fn (string $state) => $state === 'royal' ? 'warning' : 'info'),

                TextColumn::make('name')
                    ->label('ชื่อ')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order')
                    ->label('ลำดับ')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('เปิดใช้งาน')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('หมวดหมู่')
                    ->options([
                        'royal' => 'พระบรมวงศานุวงศ์',
                        'vip'   => 'บุคคลสำคัญ',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('สถานะ')
                    ->trueLabel('เปิดใช้งาน')
                    ->falseLabel('ปิดใช้งาน'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category')
            ->reorderable('order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProtectionPersons::route('/'),
            'create' => Pages\CreateProtectionPerson::route('/create'),
            'edit'   => Pages\EditProtectionPerson::route('/{record}/edit'),
        ];
    }
}
