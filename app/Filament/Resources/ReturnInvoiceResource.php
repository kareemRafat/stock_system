<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ReturnInvoice;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use App\Filament\Resources\ReturnInvoiceResource\Pages;
use App\Filament\Forms\Components\ClientDateTimeFormComponent;

class ReturnInvoiceResource extends Resource
{
    protected static ?string $model = ReturnInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'العملاء والمنتجات';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'فواتير المرتجعات';

    protected static ?string $pluralModelLabel = 'فواتير المرتجعات';

    protected static ?string $modelLabel = 'فاتورة مرتجع';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->columnSpan(2),

                Forms\Components\Hidden::make('original_invoice_id'),

                Forms\Components\TextInput::make('original_invoice_number')
                    ->label('رقم الفاتورة')
                    ->readOnly(),

                Forms\Components\TextInput::make('return_invoice_number')
                    ->label('رقم فاتورة الإرجاع')
                    ->readOnly(),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),

                // get the javascript Date
                ClientDateTimeFormComponent::make('created_at'),

                Forms\Components\Repeater::make('items')
                    ->label('الأصناف المرتجعة')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('المنتج')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Product::where('name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('name', 'id');
                            })
                            ->getOptionLabelUsing(function ($value) {
                                return \App\Models\Product::find($value)?->name;
                            })
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->readOnly(),

                        Forms\Components\TextInput::make('quantity_returned')
                            ->label('الكمية المرتجعة')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->rules([
                                'required',
                                'numeric',
                                'min:0',
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $quantity = $get('quantity') ?? 0;
                                        if ($value > $quantity) {
                                            $fail("الكمية المرتجعة ($value) لا يمكن أن تكون أكبر من الكمية الأصلية ($quantity).");
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\Checkbox::make('return_all')
                            ->label('إرجاع السلعة بالكامل')
                            ->helperText('في حالة الاختيار يتم ارجاع السلعة بالكامل'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->addActionLabel('إضافة صنف')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('return_invoice_number')
                    ->label('رقم الفاتورة')
                    ->color('indigo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('اسم العميل')
                    ->searchable(),

                Tables\Columns\TextColumn::make('original_invoice_number')
                    ->label('فاتورة المبيعات')
                    ->color('orange')
                    ->searchable()
                    ->url(fn($record) => url("/invoices/{$record->original_invoice_id}"))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('عدد الأصناف'),

                Tables\Columns\TextColumn::make('createdDate')
                    ->label('تاريخ الإنشاء'),
                Tables\Columns\TextColumn::make('createdTime')
                    ->label('وقت الإنشاء'),
            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض الفاتورة')
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->extraAttributes(['class' => 'font-semibold']),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnInvoices::route('/'),
            'create' => Pages\CreateReturnInvoice::route('/create'),
            'view' => Pages\ViewReturnInvoice::route('/{record}'),
            // 'edit' => Pages\EditReturnInvoice::route('/{record}/edit'),
        ];
    }
}
