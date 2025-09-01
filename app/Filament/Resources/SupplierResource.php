<?php

namespace App\Filament\Resources;


use Filament\Forms;
use Filament\Tables;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use App\Filament\Resources\SupplierResource\Pages;


class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'الموردين';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'الموردين';

    protected static ?string $pluralModelLabel = 'الموردين';

    protected static ?string $modelLabel = 'مورد';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المورد')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('address')
                    ->label('العنوان')
                    ->nullable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // eager loading
                Supplier::query()
                    ->withSum(['wallet as debit_sum' => function ($query) {
                        $query->whereIn('type', ['debit', 'invoice']);
                    }], 'amount')
                    ->withSum(['wallet as credit_sum' => function ($query) {
                        $query->where('type', 'credit');
                    }], 'amount')
            )
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المورد')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->color('violet'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('balance')
                    ->label('رصيد المورد')
                    ->getStateUsing(fn($record) => ($record->credit_sum - $record->debit_sum) ?? 0)
                    ->formatStateUsing(
                        fn($state) =>
                        $state == 0
                            ? '0 ج.م'
                            : number_format($state, 2) . ' ج.م'
                    )
                    ->color(
                        fn($state) =>
                        $state < 0 ? 'rose' : ($state > 0 ? 'success' : 'gray')
                    )
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(30)
                    ->default('لايوجد')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d-m-Y')
                    ->weight(FontWeight::Medium),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('wallet')
                    ->label('حركة الرصيد')
                    ->color('teal')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->url(fn($record) => route('filament.admin.resources.suppliers.wallet', $record))
                    ->icon('heroicon-o-wallet')
                    ->disabled(fn($record) => $record->balance == 0),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->extraAttributes(['class' => 'font-semibold']),
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
            'index' => Pages\ListSuppliers::route('/'),
            // 'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
            'wallet' => Pages\SupplierWalletPage::route('/{record}/wallet'),
        ];
    }
}
