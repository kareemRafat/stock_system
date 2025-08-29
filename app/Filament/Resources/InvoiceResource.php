<?php

namespace App\Filament\Resources;

use App\Filament\Actions\InvoiceActions\AddReturnAction;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Wizard;
use Filament\Navigation\NavigationItem;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Actions\InvoiceActions\PayInvoiceAction;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationGroup = 'الطلبيات والفواتير';

    protected static ?string $modelLabel = 'فاتورة'; // Singular

    protected static ?string $pluralModelLabel = 'الفواتير'; // Plural

    protected static ?string $navigationLabel = 'فواتير العملاء';

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $activeNavigationIcon = 'heroicon-s-calculator';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Order')
                        ->label('الطلب')
                        ->schema(self::getInvoiceInformation()),
                    Wizard\Step::make('order_items')
                        ->label('اصناف الفاتورة')
                        ->schema(self::getInvoiceItemsInfo()),
                ])
                    ->label('إنشاء فاتورة')
                    ->columnSpanFull()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->weight('semibold')
                    ->searchable()
                    ->formatStateUsing(fn($state) => strtoupper($state)),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('اسم العميل')
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
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
                    ->getStateUsing(function ($record) {
                        return $record->updated_at
                            ? \Carbon\Carbon::parse($record->updated_at)->format('d/m/Y')
                            : 'لم يتم التعديل ';
                    })
                    ->color(function ($record) {
                        return $record->updated_at ? null : 'danger';
                    })
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'orange',   // orange
                        'paid'      => 'success',   // green
                        'cancelled' => 'danger',    // red
                        default     => 'secondary', // gray
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending'   => 'قيد الانتظار',
                        'paid'      => 'مدفوعة',
                        'cancelled' => 'ملغاة',
                        default     => $state,
                    })
            ])
            ->filters([
                Tables\Filters\Filter::make('paid_status')
                    ->form([
                        Forms\Components\Toggle::make('pending_only')
                            ->label('عرض الفواتير غير المدفوعة')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['pending_only']) {
                            $query->where('status', 'pending');
                        }
                    })
                    ->columnSpanFull(),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('اسم العميل')
                    ->options(
                        fn() => Customer::query()->pluck('name', 'id')->toArray()
                    )
                    ->searchable()
                    ->placeholder('كل العملاء')
                    ->columnSpan(2),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض الفاتورة'),
                PayInvoiceAction::make(),
                AddReturnAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
                ]),
            ]);
    }

    public static function getInvoiceInformation()
    {
        return [
            Forms\Components\TextInput::make('invoice_number')
                ->label('رقم الفاتورة')
                ->default(fn($livewire) => $livewire instanceof CreateRecord ? Invoice::generateUniqueInvoiceNumber() : null)
                ->disabled()
                ->dehydrated() // allow it to be saved
                ->dehydrateStateUsing(fn($state) => $state) // manually pass the state
                ->required()
                ->rules(['required', 'string', 'max:255']),
            Forms\Components\Select::make('customer_id')
                ->label('اسم العميل')
                ->options(
                    fn() => Customer::query()
                        ->where('status', 'enabled')
                        ->latest()
                        ->limit(10)
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->getSearchResultsUsing(
                    fn(string $search) =>
                    Customer::query()
                        ->where('name', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('name', 'id')
                )
                ->getOptionLabelUsing(
                    fn($value): ?string =>
                    Customer::find($value)?->name
                )
                ->native(false)
                ->required()
                ->preload()
                ->helperText('اختر العميل من القائمة أو ابدأ بالكتابة للبحث عن عميل موجود'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
            Forms\Components\Hidden::make('created_at')
                ->default(now()->toDateTimeString()),

        ];
    }

    public static function getInvoiceItemsInfo()
    {
        return [
            Forms\Components\Repeater::make('items')
                ->relationship('items')
                ->label('اصناف الفاتورة')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('الصنف')
                        ->relationship(
                            name: 'product',
                            modifyQueryUsing: fn(\Illuminate\Database\Eloquent\Builder $query) =>
                            $query->where('stock_quantity', '>', 0) // only products with stock
                        )
                        // to concatinate type with name
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - {$record->type}")
                        ->required()
                        ->native(false)
                        ->live(debounce: 300)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $product  = \App\Models\Product::find($state);
                            $price    = $product?->final_price ?? 0;
                            $stock    = $product?->stock_quantity ?? 0;
                            $quantity = $get('quantity') ?? 1;

                            $set('price', $price);
                            $set('stock_quantity', $stock);
                            $set('subtotal', round($price * $quantity, 2));

                            // Recalculate total
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                        }),

                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('المتاح بالمخزن')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($set, $get) {
                            $productId = $get('product_id');
                            if ($productId) {
                                $stock = \App\Models\Product::find($productId)?->stock_quantity ?? 0;
                                $set('stock_quantity', $stock);
                            }
                        }),

                    Forms\Components\TextInput::make('quantity')
                        ->label('الكمية')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live(debounce: 300)
                        ->rule(function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $stock = $get('stock_quantity') ?? 0;
                                if ($value > $stock) {
                                    $fail("الكمية المطلوبة ($value) أكبر من المتاح في المخزن");
                                }
                            };
                        })
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $price = $get('price') ?? 0;
                            $set('subtotal', round($state * $price, 2));

                            // Recalculate total
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                        }),

                    Forms\Components\TextInput::make('price')
                        ->label('السعر')
                        ->numeric()
                        ->required()
                        ->live(debounce: 300)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $quantity = $get('quantity') ?? 1;
                            $set('subtotal', round($state * $quantity, 2));

                            // Recalculate total
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                        }),

                    Forms\Components\TextInput::make('subtotal')
                        ->label('الإجمالي')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),
                ])
                ->addActionLabel('إضافة صنف')
                ->columns(5) // was 4, now 5 because we added stock_quantity
                ->minItems(1)
                ->required(),

            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Placeholder::make('total_amount_display')
                        ->label(false)
                        ->content(function ($get) {
                            $items = $get('items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            return view('filament.partials.invoice-total', [
                                'total' => $total,
                            ]);
                        })
                        ->live(debounce: 300),
                ]),

            // Hidden field for saving total to DB
            Forms\Components\Hidden::make('total_amount')
                ->dehydrated()
                ->default(0),
        ];
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            // 'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return array_merge(
            parent::getNavigationItems(),
            [
                NavigationItem::make()
                    ->label('اضافة فاتورة جديدة')
                    ->icon('heroicon-o-plus-circle')
                    ->activeIcon('heroicon-s-plus-circle')
                    ->isActiveWhen(fn() => request()->path() === 'invoices/create')
                    ->group('الطلبيات والفواتير')
                    ->sort(2)
                    ->url(static::getUrl('create')),
            ]
        );
    }
}
