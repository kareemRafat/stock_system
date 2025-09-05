<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductPurchase;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductPurchaseResource\Pages;
use App\Filament\Resources\ProductPurchaseResource\RelationManagers;

class ProductPurchaseResource extends Resource
{
    protected static ?string $model = ProductPurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'عملية شراء';

    protected static ?string $pluralModelLabel = 'عمليات الشراء للمخزن';

    protected static ?string $navigationGroup = 'إدارة المخزون';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn($query) =>
                $query->with(['product', 'supplier'])
            )
            ->defaultSort('purchase_date', 'desc')
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable()
                    ->weight('semibold')
                    ->color('violet'),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->suffix(' ج.م ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('التكلفة الإجمالية')
                    ->suffix(' ج.م ')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('EGP'),
                    ]),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('تاريخ الشراء')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier_invoice_number')
                    ->label('رقم فاتورة المورد')
                    ->toggleable(true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->options(
                        fn() => Supplier::query()
                            ->latest()
                            ->limit(20) // أول 20
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search) => Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(
                        fn($value): ?string => Supplier::find($value)?->name
                    )
                    ->native(false),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('المنتج')
                    ->options(
                        fn() => Product::query()
                            ->latest()
                            ->limit(20)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search) => Product::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(
                        fn($value): ?string => Product::find($value)?->name
                    )
                    ->native(false),

                Tables\Filters\Filter::make('purchase_date')
                    ->form([
                        Forms\Components\Grid::make(2) // Use grid with 2 columns
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('من تاريخ')
                                    ->native(false)
                                    ->placeholder('اختار تاريخ بدء الفلتر'),
                                Forms\Components\DatePicker::make('until')
                                    ->label('إلى تاريخ')
                                    ->native(false)
                                    ->placeholder('اختار تاريخ نهاية الفلتر'),
                            ]),
                    ])
                    ->columnSpanfull()
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('purchase_date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('purchase_date', '<=', $data['until']));
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListProductPurchases::route('/'),
            'create' => Pages\CreateProductPurchase::route('/create'),
            'edit' => Pages\EditProductPurchase::route('/{record}/edit'),
            'view' => Pages\ViewProductPurchase::route('/{record}')
        ];
    }
}
