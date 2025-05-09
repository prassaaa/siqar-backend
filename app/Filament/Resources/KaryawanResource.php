<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaryawanResource\Pages;
use App\Models\Karyawan;
use App\Models\Pengguna;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    
    protected static ?string $navigationLabel = 'Karyawan';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Karyawan')
                    ->schema([
                        Forms\Components\Select::make('pengguna_id')
                            ->label('Akun Pengguna')
                            ->options(Pengguna::where('peran', 'karyawan')->pluck('nama', '_id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('jabatan')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('departemen')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('no_telepon')
                            ->tel()
                            ->required()
                            ->maxLength(15),
                        Forms\Components\Textarea::make('alamat')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('tanggal_bergabung')
                            ->required(),
                        Forms\Components\Select::make('status_karyawan')
                            ->options([
                                'tetap' => 'Karyawan Tetap',
                                'kontrak' => 'Karyawan Kontrak',
                                'magang' => 'Magang',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jabatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departemen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_karyawan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tetap' => 'success',
                        'kontrak' => 'warning',
                        'magang' => 'info',
                    }),
                Tables\Columns\TextColumn::make('tanggal_bergabung')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_karyawan')
                    ->options([
                        'tetap' => 'Karyawan Tetap',
                        'kontrak' => 'Karyawan Kontrak',
                        'magang' => 'Magang',
                    ]),
                Tables\Filters\SelectFilter::make('departemen')
                    ->options(function () {
                        return Karyawan::distinct('departemen')->pluck('departemen', 'departemen')->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
    
    // public static function getWidgets(): array
    // {
    //     return [
    //         KaryawanResource\Widgets\KaryawanStats::class,
    //     ];
    // }
}