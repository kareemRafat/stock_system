<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CustomerResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Actions\CustomerActions\AdjustBalanceAction;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Filament\Actions\CustomerActions\ViewCustomerDetailsAction;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationGroup = 'العملاء والمنتجات';

    protected static ?string $modelLabel = 'عميل'; // Singular

    protected static ?string $pluralModelLabel = 'العملاء'; // Plural

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $activeNavigationIcon = 'heroicon-s-newspaper';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('إسم العميل')
                    ->unique(ignoreRecord: true)
                    ->rules(['required', 'string', 'max:255'])
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label('رقم التواصل')
                    ->unique(ignoreRecord: true)
                    ->rules(['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'])
                    ->required()
                    ->helperText("رقم التواصل مطلوب لعملية تسجيل العميل"),
                Forms\Components\TextInput::make('phone2')
                    ->tel()
                    ->rules(['nullable', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'])
                    ->label('رقم احتياطي')
                    ->unique(ignoreRecord: true)
                    ->helperText("يمكن ترك الحقل فارغاً"),
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rules(['required', 'string', 'max:1000'])
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('city')
                    ->label('المدينة')
                    ->rules(['required', 'string', 'max:255'])
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('governorate')
                    ->label('المحافظة')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->rules(['required', 'string', 'max:255']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('إسم العميل')
                    ->searchable()
                    ->fontFamily(FontFamily::Sans)
                    ->color('indigo')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->label('رقم التواصل')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('balance')
                    ->label('رصيد العميل')
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
                    ->weight(FontWeight::Medium)
                    ->limit(20),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date("d-m-Y")
                    ->sortable()
                    ->weight(FontWeight::Medium),
            ])
            ->filters([
                //
            ])
            ->actions([
                // ViewCustomerDetailsAction::make(),
                Tables\Actions\ViewAction::make()
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->tooltip(' تفاصيل العميل')
                    ->label('عرض التفاصيل'),
                ActionGroup::make([
                    AdjustBalanceAction::make(),
                    Tables\Actions\EditAction::make()
                        ->extraAttributes(['class' => 'font-semibold']),
                    Tables\Actions\DeleteAction::make()
                        ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin')
                        ->extraAttributes(['class' => 'font-semibold']),
                ])
                    ->label('المزيد')
                    ->button()
                    ->color('gray')
                    ->size('xs')
                    ->tooltip('إجراءات إضافية'),
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
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            // 'create' => Pages\CreateCustomer::route('/create'),
            // 'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
