<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\ActionGroup;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Actions\CustomerActions\AdjustBalanceAction;


class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationGroup = 'العملاء والمنتجات';

    protected static ?int $navigationSort = 1;

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
                    ->required(),
                Forms\Components\ToggleButtons::make('status')
                    ->label('الحالة')
                    ->options([
                        'enabled' => 'مفعل',
                        'disabled' => 'معطل',
                    ])
                    ->colors([
                        'enabled' => 'success',
                        'disabled' => 'danger',
                    ])
                    ->icons([
                        'enabled' => 'heroicon-o-check-circle',
                        'disabled' => 'heroicon-o-x-circle',
                    ])
                    ->inline()
                    ->required()
                    ->default('enabled'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label('رقم التواصل')
                    ->rules(['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'])
                    ->required()
                    ->helperText("رقم التواصل مطلوب لعملية تسجيل العميل"),
                Forms\Components\TextInput::make('phone2')
                    ->tel()
                    ->rules(['nullable', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'])
                    ->label('رقم احتياطي')
                    ->helperText("يمكن ترك الحقل فارغاً"),
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rules(['required', 'string', 'max:1000'])
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('city')
                    ->label('المدينة')
                    ->rules(['required', 'string', 'max:255'])
                    ->required(),
                Forms\Components\TextInput::make('governorate')
                    ->label('المحافظة')
                    ->required()
                    ->rules(['required', 'string', 'max:255']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // eager loading
                Customer::query()
                    ->withSum(['wallet as debit_sum' => function ($query) {
                        $query->whereIn('type', ['debit', 'invoice']);
                    }], 'amount')
                    ->withSum(['wallet as credit_sum' => function ($query) {
                        $query->where('type', 'credit');
                    }], 'amount')
            )
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
                    ->getStateUsing(fn($record) => ($record->credit_sum - $record->debit_sum) ?? 0)
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date("d-m-Y")
                    ->sortable()
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'enabled' => 'success', // Yellow badge for enabled
                        'disabled' => 'warning', // Green badge for disabled

                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'enabled' => 'مفعل',
                        'disabled' => 'معطل',
                    })
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->extraAttributes(['class' => 'font-semibold'])
                    ->tooltip(' تفاصيل العميل')
                    ->label('عرض التفاصيل'),
                ActionGroup::make([
                    AdjustBalanceAction::make(),
                    Tables\Actions\Action::make('wallet')
                        ->label('حركة الرصيد')
                        ->color('teal')
                        ->extraAttributes(['class' => 'font-semibold'])
                        ->url(fn($record) => route('filament.admin.resources.customers.wallet', $record))
                        ->icon('heroicon-o-wallet'),
                    Tables\Actions\EditAction::make()
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
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'wallet' => Pages\CustomerWalletPage::route('/{record}/wallet'),
            // 'create' => Pages\CreateCustomer::route('/create'),
            // 'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
