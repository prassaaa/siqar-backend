<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\QRCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    
    protected static ?string $navigationLabel = 'Absensi';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Absensi')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->options(Karyawan::with('pengguna')->get()->pluck('nama_lengkap', '_id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('qrcode_id')
                            ->label('QR Code')
                            ->options(QRCode::pluck('deskripsi', '_id'))
                            ->searchable(),
                        Forms\Components\DatePicker::make('tanggal')
                            ->required(),
                        Forms\Components\TimePicker::make('waktu_masuk'),
                        Forms\Components\TimePicker::make('waktu_keluar'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'hadir' => 'Hadir',
                                'terlambat' => 'Terlambat',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'alpha' => 'Alpha',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('keterangan')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('karyawan.nama_lengkap')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('karyawan.nip')
                    ->label('NIP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_masuk')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_keluar')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'izin' => 'info',
                        'sakit' => 'info',
                        'alpha' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->where('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->where('tanggal', '<=', $date),
                            );
                    }),
                SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpha' => 'Alpha',
                    ]),
                SelectFilter::make('karyawan_id')
                    ->label('Karyawan')
                    ->options(function () {
                        return Karyawan::with('pengguna')->get()->pluck('nama_lengkap', '_id')->toArray();
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            $data = $records->map(function ($record) {
                                return [
                                    'karyawan' => $record->karyawan->nama_lengkap,
                                    'nip' => $record->karyawan->nip,
                                    'tanggal' => $record->tanggal->format('d-m-Y'),
                                    'waktu_masuk' => $record->waktu_masuk ? $record->waktu_masuk->format('H:i') : '-',
                                    'waktu_keluar' => $record->waktu_keluar ? $record->waktu_keluar->format('H:i') : '-',
                                    'status' => $record->status,
                                    'keterangan' => $record->keterangan,
                                ];
                            });
                            
                            $pdf = Pdf::loadView('exports.absensi', [
                                'records' => $data,
                                'tanggal_cetak' => Carbon::now()->format('d-m-Y H:i'),
                            ]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'laporan-absensi-' . Carbon::now()->format('d-m-Y') . '.pdf'
                            );
                        }),
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
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
    
    public static function getWidgets(): array
    {
        return [
            AbsensiResource\Widgets\AbsensiChart::class,
        ];
    }
}