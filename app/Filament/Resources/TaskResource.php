<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\ProtectionPerson;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'ภารกิจ';

    protected static ?string $modelLabel = 'ภารกิจ';

    protected static ?string $pluralModelLabel = 'ภารกิจ';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('task_tabs')->tabs([

                /* ─── Tab 1: ข้อมูลทั่วไป ─── */
                Tab::make('ข้อมูลทั่วไป')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('title')
                            ->label('ชื่อภารกิจ')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('task_type_key')
                            ->label('ประเภทภารกิจ')
                            ->options([
                                'royal_security' => 'ถวายความปลอดภัย',
                                'vip_protection' => 'อารักขา',
                                'convoy'         => 'นำขบวน',
                                'traffic'        => 'จราจร',
                                'venue_security' => 'อารักขาสถานที่',
                            ])
                            ->required()
                            ->live(),

                        Select::make('priority')
                            ->label('ความสำคัญ')
                            ->options([
                                'low'    => 'ต่ำ',
                                'medium' => 'ปานกลาง',
                                'high'   => 'สูง',
                                'urgent' => 'เร่งด่วน',
                            ])
                            ->default('medium')
                            ->required(),

                        Select::make('status')
                            ->label('สถานะ')
                            ->options([
                                'pending'  => 'รอดำเนินการ',
                                'progress' => 'กำลังดำเนินการ',
                                'on-hold'  => 'พักไว้',
                                'success'  => 'สำเร็จ',
                                'cancel'   => 'ยกเลิก',
                            ])
                            ->default('pending')
                            ->required(),

                        DateTimePicker::make('deadline_at')
                            ->label('วันปฏิบัติภารกิจ')
                            ->displayFormat('d/m/Y H:i')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('รายละเอียด')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                /* ─── Tab 2: บุคคลอารักขา ─── */
                Tab::make('บุคคลอารักขา')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Section::make('พระบรมวงศานุวงศ์')
                            ->description('สำหรับงานถวายความปลอดภัย')
                            ->schema([
                                Select::make('content.royal_name')
                                    ->label('บุคคลอารักขา (พระบรมวงศานุวงศ์)')
                                    ->options(fn () => ProtectionPerson::active()
                                        ->byCategory('royal')
                                        ->orderBy('order')
                                        ->pluck('name', 'name'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('เลือกพระบรมวงศานุวงศ์...')
                                    ->visible(fn (Get $get) => in_array($get('task_type_key'), ['royal_security']))
                                    ->columnSpanFull(),

                                TextInput::make('content.origin')
                                    ->label('ออกมาจากที่ใด')
                                    ->placeholder('สถานที่ต้นทาง')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'royal_security')
                                    ->columnSpanFull(),

                                TextInput::make('content.destination')
                                    ->label('จะเสด็จไปที่ไหน')
                                    ->placeholder('สถานที่ปลายทาง')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'royal_security')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('บุคคลสำคัญ')
                            ->description('สำหรับงานอารักขา VIP')
                            ->schema([
                                Select::make('content.vip_name')
                                    ->label('บุคคลอารักขา (บุคคลสำคัญ)')
                                    ->options(fn () => ProtectionPerson::active()
                                        ->byCategory('vip')
                                        ->orderBy('order')
                                        ->pluck('name', 'name'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('เลือกบุคคลสำคัญ...')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'vip_protection')
                                    ->columnSpanFull(),

                                TextInput::make('content.vehicle_info')
                                    ->label('ทะเบียน สี ยี่ห้อ รถ')
                                    ->placeholder('เช่น กก-1234 สีดำ Toyota Camry')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'vip_protection')
                                    ->columnSpanFull(),

                                TextInput::make('content.origin')
                                    ->label('เดินทางออกจากที่ไหน')
                                    ->placeholder('สถานที่ต้นทาง')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'vip_protection')
                                    ->columnSpanFull(),

                                DateTimePicker::make('content.arrival_time')
                                    ->label('เวลาออกเดินทางมายังพื้นที่ ทอ.')
                                    ->displayFormat('d/m/Y H:i')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'vip_protection')
                                    ->columnSpanFull(),

                                TextInput::make('content.destination')
                                    ->label('จะเดินทางไปที่ไหน')
                                    ->placeholder('สถานที่ปลายทาง')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'vip_protection')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                /* ─── Tab 3: รายละเอียดภารกิจอื่น ─── */
                Tab::make('รายละเอียดภารกิจ')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('นำขบวน')
                            ->schema([
                                TextInput::make('content.vehicle_or_group')
                                    ->label('ทะเบียน/ยี่ห้อรถ หรือชื่อคณะ')
                                    ->placeholder('เช่น กก-1234 หรือ คณะกรรมาธิการฯ')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'convoy')
                                    ->columnSpanFull(),
                                TextInput::make('content.destination')
                                    ->label('ไปที่ไหน')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'convoy')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Get $get) => $get('task_type_key') === 'convoy'),

                        Section::make('จราจร')
                            ->schema([
                                TextInput::make('content.vehicle_info')
                                    ->label('ทะเบียน สี ยี่ห้อ รถ')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'traffic')
                                    ->columnSpanFull(),
                                TextInput::make('content.venue')
                                    ->label('สถานที่จัดงาน')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'traffic')
                                    ->columnSpanFull(),
                                Textarea::make('content.parking_allowed')
                                    ->label('ใครจอดได้บ้าง')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'traffic')
                                    ->columnSpanFull(),
                                TextInput::make('content.traffic_direction')
                                    ->label('เดินรถทางไหน')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'traffic')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Get $get) => $get('task_type_key') === 'traffic'),

                        Section::make('อารักขาสถานที่')
                            ->schema([
                                TextInput::make('content.date_range')
                                    ->label('วันที่เริ่ม - วันที่สิ้นสุด')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'venue_security')
                                    ->columnSpanFull(),
                                TextInput::make('content.start_time')
                                    ->label('เวลางานเริ่มกี่โมง')
                                    ->visible(fn (Get $get) => $get('task_type_key') === 'venue_security')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Get $get) => $get('task_type_key') === 'venue_security'),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('ชื่อภารกิจ')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('task_type_key')
                    ->label('ประเภท')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'royal_security' => 'ถวายความปลอดภัย',
                        'vip_protection' => 'อารักขา',
                        'convoy'         => 'นำขบวน',
                        'traffic'        => 'จราจร',
                        'venue_security' => 'อารักขาสถานที่',
                        default          => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'royal_security' => 'warning',
                        'vip_protection' => 'success',
                        'convoy'         => 'info',
                        'traffic'        => 'danger',
                        'venue_security' => 'primary',
                        default          => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('สถานะ')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'  => 'รอดำเนินการ',
                        'progress' => 'กำลังดำเนินการ',
                        'on-hold'  => 'พักไว้',
                        'success'  => 'สำเร็จ',
                        'cancel'   => 'ยกเลิก',
                        default    => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending'  => 'warning',
                        'progress' => 'info',
                        'success'  => 'success',
                        'cancel'   => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('priority')
                    ->label('ความสำคัญ')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'low'    => 'ต่ำ',
                        'medium' => 'ปานกลาง',
                        'high'   => 'สูง',
                        'urgent' => 'เร่งด่วน',
                        default  => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'low'    => 'gray',
                        'medium' => 'warning',
                        'high'   => 'danger',
                        'urgent' => 'danger',
                        default  => 'gray',
                    }),

                TextColumn::make('deadline_at')
                    ->label('วันปฏิบัติ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('สร้างโดย')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('task_type_key')
                    ->label('ประเภทภารกิจ')
                    ->options([
                        'royal_security' => 'ถวายความปลอดภัย',
                        'vip_protection' => 'อารักขา',
                        'convoy'         => 'นำขบวน',
                        'traffic'        => 'จราจร',
                        'venue_security' => 'อารักขาสถานที่',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('สถานะ')
                    ->options([
                        'pending'  => 'รอดำเนินการ',
                        'progress' => 'กำลังดำเนินการ',
                        'on-hold'  => 'พักไว้',
                        'success'  => 'สำเร็จ',
                        'cancel'   => 'ยกเลิก',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
