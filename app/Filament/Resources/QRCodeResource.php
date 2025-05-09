<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QRCodeResource\Pages;
use App\Models\QRCode;
use App\Models\Lokasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class QRCodeResource extends Resource
{
    protected static ?string $model = QRCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    
    protected static ?string $navigationLabel = 'QR Code';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi QR Code')
                    ->schema([
                        Forms\Components\TextInput::make('deskripsi')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('tanggal')
                            ->required(),
                        Forms\Components\TimePicker::make('waktu_mulai')
                            ->required(),
                        Forms\Components\TimePicker::make('waktu_berakhir')
                            ->required(),
                        Forms\Components\Select::make('lokasi_id')
                            ->label('Lokasi')
                            ->options(Lokasi::where('status', 'aktif')->pluck('nama_lokasi', '_id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'aktif' => 'Aktif',
                                'nonaktif' => 'Nonaktif',
                            ])
                            ->default('aktif')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_mulai')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_berakhir')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lokasi.nama_lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'nonaktif' => 'gray',
                    }),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),
                Tables\Filters\SelectFilter::make('lokasi_id')
                    ->label('Lokasi')
                    ->options(function () {
                        return Lokasi::pluck('nama_lokasi', '_id')->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('generate')
                    ->label('Generate QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function (QRCode $record) {
                        // Generate a unique code
                        $uniqueCode = Str::random(16);
                        
                        // Save the code to the record
                        $record->kode = $uniqueCode;
                        $record->dibuat_oleh = auth()->id();
                        $record->save();
                        
                        // Generate QR Code image
                        $qrCode = QrCodeGenerator::format('png')
                            ->size(300)
                            ->errorCorrection('H')
                            ->generate($uniqueCode);
                            
                        // Save QR Code to storage
                        $path = 'public/qrcodes/qrcode-' . $record->id . '.png';
                        \Storage::put($path, $qrCode);
                        
                        // Send notification
                        Notification::make()
                            ->title('QR Code Berhasil Dibuat')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (QRCode $record): bool => empty($record->kode)),
                Tables\Actions\Action::make('view_qr')
                    ->label('Lihat QR')
                    ->icon('heroicon-o-eye')
                    ->url(fn (QRCode $record): string => route('qrcode.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (QRCode $record): bool => !empty($record->kode)),
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
            'index' => Pages\ListQRCodes::route('/'),
            'create' => Pages\CreateQRCode::route('/create'),
            'edit' => Pages\EditQRCode::route('/{record}/edit'),
        ];
    }
}