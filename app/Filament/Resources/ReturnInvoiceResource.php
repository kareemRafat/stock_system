<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnInvoiceResource\Pages;
use App\Models\ReturnInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                            ->required(),

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
            ->columns([
                Tables\Columns\TextColumn::make('return_invoice_number')
                    ->label('رقم الفاتورة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('اسم العميل')
                    ->searchable(),

                Tables\Columns\TextColumn::make('original_invoice_number')
                    ->label('فاتورة المبيعات')
                    ->searchable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('عدد الأصناف'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d-m-Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
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
            // 'edit' => Pages\EditReturnInvoice::route('/{record}/edit'),
        ];
    }
}
