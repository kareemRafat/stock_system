<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Wizard;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\InvoiceResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InvoiceResource\RelationManagers;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationGroup = 'الطلبيات والفواتير';

    protected static ?string $modelLabel = 'فاتورة'; // Singular

    protected static ?string $pluralModelLabel = 'الفواتير'; // Plural

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
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('اسم العميل')
                    ->weight('semibold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->suffix(' جنيه ')
                    ->sortable()
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('اسم العميل')
                    ->options(
                        fn() => Customer::query()->pluck('name', 'id')->toArray()
                    )
                    ->searchable()
                    ->placeholder('كل العملاء'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض الفاتورة'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
                        ->relationship('product', 'name')
                        ->required()
                        ->native(false)
                        ->live(debounce: 300)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $product = \App\Models\Product::find($state);
                            $price = $product?->final_price ?? 0;
                            $quantity = $get('quantity') ?? 1;

                            $set('price', $price);
                            $set('subtotal', round($price * $quantity, 2));

                            // Recalculate total
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                            static::updateRemaining($set, $get);
                        }),

                    Forms\Components\TextInput::make('quantity')
                        ->label('الكمية')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live(debounce: 300)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $price = $get('price') ?? 0;
                            $set('subtotal', round($state * $price, 2));

                            // Recalculate total
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                            static::updateRemaining($set, $get);
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
                            static::updateRemaining($set, $get);
                        }),

                    Forms\Components\TextInput::make('subtotal')
                        ->label('الإجمالي')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),
                ])
                ->addActionLabel('إضافة صنف')
                ->columns(4)
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

            // Optional hidden field for saving to DB
            Forms\Components\Hidden::make('total_amount')
                ->dehydrated()
                ->default(0),

            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Toggle::make('removeFromWallet')
                        ->label('خصم من محفظة العميل')
                        ->default(false)
                        ->dehydrated(fn($state) => $state !== null)
                        ->helperText('إذا كان العميل لديه رصيد في المحفظة، سيتم خصم إجمالي الفاتورة من رصيده')
                        ->columnSpan(2)
                        ->inline(false),
                    Forms\Components\TextInput::make('paid')
                        ->label('المبلغ المدفوع')
                        ->numeric()
                        ->default(0)
                        ->dehydrated()
                        ->live(debounce: 300)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $total = $get('total_amount') ?? 0;
                            $set('remaining', round($total - $state, 2));
                        })
                        ->helperText('فى حالة تبقى مبلغ سيتم اضافته الى محفظة العميل')
                        ->columnSpan(1),
                ])
                ->columns(3),
            Forms\Components\TextInput::make('remaining'),
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
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    protected static function updateRemaining(callable $set, callable $get): void
    {
        $total = $get('../../total_amount') ?? 0;
        $paid = $get('../../paid') ?? 0;

        $set('../../remaining', round($total - $paid, 2));
    }
}
