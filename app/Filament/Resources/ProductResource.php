<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'العملاء والمنتجات';

    protected static ?string $modelLabel = 'منتج'; // Singular

    protected static ?string $pluralModelLabel = 'المنتجات'; // Plural

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $activeNavigationIcon = 'heroicon-s-bolt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->rules('required')
                    ->label('اسم المنتج')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('unit')
                    ->label('وحدة القياس')
                    ->rules('required')
                    ->required()
                    ->placeholder('كرتونة - قطعة - كيلو إلخ'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->rules('required')
                    ->label('السعر')
                    ->numeric()
                    ->suffix('جنيه'),
                Forms\Components\TextInput::make('discount')
                    ->required()
                    ->rules('required')
                    ->numeric()
                    ->label('الخصم')
                    ->suffix(' %')
                    ->default(0),
                Forms\Components\TextInput::make('stock_quantity')
                    ->label('الكيمة المتاحة بالمخزن')
                    ->required()
                    ->rules('required')
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('وصف المنتج'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null) // prevent clickable row
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->color('purple')
                    ->label('الاسم')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('unit')
                    ->label('الوحدة')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->label('الكمية المتوفرة')
                    ->formatStateUsing(fn($state) => $state == 0 ? 'لاتوجد' : $state)
                    ->color(fn($state) => $state == 0 ? 'danger' : ($state < 20 ? 'orange' : null))
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->suffix(' جنيه ')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('discount')
                    ->numeric()
                    ->label('الخصم')
                    ->suffix(' %')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date("d-m-Y")
                    ->label('تاريخ التعديل')
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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
            'index' => Pages\ListProducts::route('/'),
            // 'create' => Pages\CreateProduct::route('/create'),
            // 'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
