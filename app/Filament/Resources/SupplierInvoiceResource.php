<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SupplierInvoice;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Filament\Resources\SupplierInvoiceResource\RelationManagers;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'الموردين';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'فاتورة مورد';

    protected static ?string $pluralModelLabel = 'فواتير الموردين';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->options(
                        fn() => Supplier::query()
                            ->latest()
                            ->limit(10)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search) =>
                        Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->required(),
                Forms\Components\TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->numeric()
                    ->prefix('جنيه')
                    ->required(),

                Forms\Components\DatePicker::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->native(false)
                    ->placeholder('اختر تاريخ الفاتورة'),

                Forms\Components\Repeater::make('items')
                    ->label('الأصناف')
                    ->columnSpanFull()
                    ->relationship('items') // SupplierInvoice hasMany SupplierInvoiceItem
                    ->schema([
                        Forms\Components\Select::make('product_id') // Use the foreign key for the relationship
                            ->label('اسم المنتج')
                            ->relationship(name: 'product', titleAttribute: 'name')
                            ->required()
                            ->searchable() // Optional: Enable search for easier product selection
                            ->preload(), // Optional: Load options immediately

                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('سعر الوحدة')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('sell_price')
                            ->label('سعر البيع')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('subtotal')
                            ->label('الإجمالي')
                            ->numeric()
                            ->required()
                            ->dehydrated(true) // يتخزن في الداتابيز
                            ->live(debounce: 500),
                    ])
                    ->columns(5)
                    ->addActionLabel('إضافة صنف جديد')
                    ->defaultItems(1),
                Forms\Components\Placeholder::make('total_placeholder')
                    ->label('الإجمالي الكلي')
                    ->content(
                        fn($get) =>
                        collect($get('items') ?? [])
                            ->sum(fn($item) => (int) ($item['subtotal'] ?? 0)) . ' جنيه'
                    )
                    ->extraAttributes([
                        'class' => 'bg-primary-600 text-white border rounded-lg shadow-sm p-3 filament-forms-input'
                    ])
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // تعطيل الضغط على الصف
            ->recordAction(null)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->weight('semibold')
                    ->searchable()
                    ->formatStateUsing(fn($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('اسم المورد')
                    ->weight('semibold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->suffix(' جنيه ')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الفاتورة')
                    ->color('primary')
                    ->date("d/m/Y")
                    ->sortable()
                    ->weight('semibold'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('اسم المورد')
                    ->options(fn() => Supplier::query()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->placeholder('كل الموردين')
                    ->columnSpan(2),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض الفاتورة'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->extraAttributes(['class' => 'font-semibold'])
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
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
            'index' => Pages\ListSupplierInvoices::route('/'),
            'create' => Pages\CreateSupplierInvoice::route('/create'),
            'view' => Pages\ViewSupplierInvoice::route('/{record}'),
            'edit' => Pages\EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
