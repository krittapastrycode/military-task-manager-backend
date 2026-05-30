<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'ผู้ใช้งาน (Users)';

    protected static ?string $modelLabel = 'ผู้ใช้งาน';

    protected static ?string $pluralModelLabel = 'ผู้ใช้งาน';

    protected static ?string $navigationGroup = 'ผู้ใช้งาน / ผู้ดูแล';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'users';

    public static function canViewAny(): bool
    {
        return in_array('admin', (array) (auth()->user()?->role ?? []));
    }

    public static function getEloquentQuery(): Builder
    {
        // Show only users whose roles don't include admin or commander
        return parent::getEloquentQuery()
            ->whereRaw("NOT (role @> '[\"admin\"]'::jsonb)")
            ->whereRaw("NOT (role @> '[\"commander\"]'::jsonb)");
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('ชื่อ-นามสกุล')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('อีเมล')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->label('รหัสผ่าน')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            TextInput::make('rank')
                ->label('ยศ')
                ->maxLength(100),

            TextInput::make('position')
                ->label('ตำแหน่ง')
                ->maxLength(255),

            TextInput::make('unit')
                ->label('หน่วย')
                ->maxLength(255),

            TextInput::make('phone')
                ->label('เบอร์โทรศัพท์')
                ->tel()
                ->maxLength(20),

            \Filament\Forms\Components\Hidden::make('role')->default(['user']),

            Toggle::make('is_active')
                ->label('เปิดใช้งาน')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('ชื่อ-นามสกุล')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('อีเมล')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('rank')
                    ->label('ยศ')
                    ->searchable(),

                TextColumn::make('position')
                    ->label('ตำแหน่ง')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('unit')
                    ->label('หน่วย')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('โทรศัพท์')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('สถานะ')
                    ->boolean(),

                TextColumn::make('role')
                    ->label('สิทธิ์')
                    ->formatStateUsing(function ($state): string {
                        $map = ['admin' => 'ผู้ดูแลระบบ', 'commander' => 'ผู้บังคับบัญชา', 'user' => 'ผู้ใช้งาน'];
                        $roles = is_array($state) ? $state : (array) $state;
                        return implode(', ', array_map(fn($r) => $map[$r] ?? $r, $roles));
                    }),

                TextColumn::make('tasks_count')
                    ->label('จำนวนภารกิจ')
                    ->counts('tasks')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('สร้างเมื่อ')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
