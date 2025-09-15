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
use App\Filament\Forms\Components\ClientDateTimeFormComponent;
use Filament\Support\Enums\FontWeight;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationGroup = 'العملاء والمنتجات';

    protected static ?int $navigationSort = 3;

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
            ->modifyQueryUsing(function ($query) {
                // Eager load the necessary relationships
                return $query->with([
                    'returnInvoices',
                ]);
            })
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->suffix(' جنيه '),
                Tables\Columns\TextColumn::make('createdDate')
                    ->label('تاريخ الفاتورة')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('has_returns')
                    ->label('هل بها مرتجع؟')
                    ->extraAttributes(['class' => 'text-sm'])
                    ->icon(
                        fn($record) => $record->has_returns
                            ? 'heroicon-o-arrow-path'
                            : 'heroicon-o-check'
                    )
                    ->iconPosition('before') // or 'after'
                    ->color(fn($record) => $record->has_returns ? 'danger' : 'success')
                    ->formatStateUsing(fn($record) => $record->has_returns ? 'مرتجع' : 'لا'),
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
                    ->searchable()
                    ->options(function () {
                        // get 10 when open
                        return Customer::limit(10)->pluck('name', 'id')->toArray();
                    })
                    ->getOptionLabelUsing(fn($value) => Customer::find($value)?->name)
                    ->getSearchResultsUsing(function ($search) {
                        return Customer::where('name', 'like', "%{$search}%")
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('كل العملاء')
                    ->columnSpan(2),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض الفاتورة'),
                PayInvoiceAction::make(),
                // AddReturnAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->extraAttributes(['class' => 'font-semibold'])
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
                ->searchable()
                ->native(false)
                ->required()
                ->preload(true)
                ->label('اسم العميل')
                ->searchable()
                ->options(function () {
                    // get 10 when open
                    return Customer::where('status', 'enabled')->limit(10)->pluck('name', 'id')->toArray();
                })
                ->getOptionLabelUsing(fn($value) => Customer::find($value)?->name)
                ->getSearchResultsUsing(function ($search) {
                    return Customer::where('name', 'like', "%{$search}%")
                        ->where('status', 'enabled')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->placeholder('كل العملاء'),


            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),

            // get the javascript Date
            ClientDateTimeFormComponent::make('created_at')
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
                        ->options(function () {
                            static $options;

                            if (!$options) {
                                $options = \App\Models\Product::where('stock_quantity', '>', 0)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }

                            return $options;
                        })
                        // ->preload()
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - {$record->type}")
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->reactive()
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
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('quantity')
                        ->label('الكمية')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live(debounce: 500)
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
                        ->disabled()
                        ->dehydrated(),

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
}
