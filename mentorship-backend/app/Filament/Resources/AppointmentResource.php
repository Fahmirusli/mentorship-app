<?php 

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Mentorship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Mentorship Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Appointment Details')
                    ->schema([
                        Forms\Components\Select::make('mentorship_id')
                            ->label('Mentorship')
                            ->options(function () {
                                return Mentorship::with(['mentor', 'mentee'])
                                    ->get()
                                    ->mapWithKeys(function ($mentorship) {
                                        return [$mentorship->id => $mentorship->mentor->name . ' - ' . $mentorship->mentee->name];
                                    });
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required()
                            ->native(false)
                            ->minDate(now()),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->required()
                            ->default(60)
                            ->suffix('minutes')
                            ->minValue(15)
                            ->maxValue(180),
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'rescheduled' => 'Rescheduled',
                            ])
                            ->required()
                            ->default('scheduled'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Meeting Information')
                    ->schema([
                        Forms\Components\TextInput::make('meeting_link')
                            ->url()
                            ->placeholder('https://zoom.us/j/123456789'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->placeholder('Add any notes about this appointment...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mentorship.mentor.name')
                    ->label('Mentor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mentorship.mentee.name')
                    ->label('Mentee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'warning' => 'rescheduled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'rescheduled' => 'Rescheduled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}