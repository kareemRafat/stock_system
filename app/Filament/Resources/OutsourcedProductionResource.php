<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\OutsourcedProduction;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OutsourcedProductionResource\Pages;
use App\Filament\Resources\OutsourcedProductionResource\RelationManagers;

class OutsourcedProductionResource extends Resource
{
    protected static ?string $model = OutsourcedProduction::class;

    protected static ?string $navigationGroup = 'العملاء والمنتجات';

    protected static ?string $modelLabel = 'اوردر تصنيع'; // Singular

    protected static ?string $pluralModelLabel = 'التصنيع الخارجي'; // Plural

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $activeNavigationIcon = 'heroicon-s-building-office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->label('اسم الصنف')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('factory_name')
                    ->label('اسم المصنع')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('size')
                    ->label('المقاس')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('total_cost')
                    ->label('التكلفة')
                    ->numeric()
                    ->suffix('ج.م'),

                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البدء')
                    ->displayFormat('d-m-Y')
                    ->required()
                    ->native(false),

                Forms\Components\DatePicker::make('actual_delivery_date')
                    ->label('تاريخ التسليم')
                    ->displayFormat('d-m-Y')
                    ->native(false),

                Forms\Components\ToggleButtons::make('status')
                    ->label('الحالة')
                    ->options([
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'canceled' => 'ملغي',
                    ])
                    ->colors([
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'canceled' => 'danger',
                    ])
                    ->icons([
                        'in_progress' => 'heroicon-o-cog',
                        'completed' => 'heroicon-o-check-circle',
                        'canceled' => 'heroicon-o-x-circle',
                    ])
                    ->inline()
                    ->required()
                    ->default('in_progress'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null) // prevent clickable row
            ->recordUrl(null) // prevent clickable row
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('اسم الصنف')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم النسخ')
                    ->copyMessageDuration(1500)
                    ->color('indigo'),
                    Tables\Columns\TextColumn::make('factory_name')
                    ->label('اسم المصنع')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->weight(FontWeight::Medium)
                    ->numeric(),
                Tables\Columns\TextColumn::make('size')
                    ->label('المقاس')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('التكلفة')
                    ->weight(FontWeight::Medium)
                    ->numeric()
                    ->suffix(' ج.م '),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->weight(FontWeight::Medium)
                    ->date("d-m-Y")
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_delivery_date')
                    ->label('تاريخ التسليم')
                    ->weight(FontWeight::Medium)
                    ->date("d-m-Y")
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->weight(FontWeight::Medium)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'canceled' => 'ملغي',
                        default => $state,
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListOutsourcedProductions::route('/'),
            'edit' => Pages\EditOutsourcedProduction::route('/{record}/edit'),
        ];
    }
}
