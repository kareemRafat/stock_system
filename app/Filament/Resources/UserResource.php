<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'فـريـق الـعـمـل';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'موظف'; // Singular

    protected static ?string $pluralModelLabel = 'الموظفين'; // Plural

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $activeNavigationIcon = 'heroicon-s-user-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الإسم بالعربي')
                    ->rule(['regex:/^[\p{Arabic}\s]+$/u', 'required'])
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('username')
                    ->label('إسم الدخول باللغة الإنجليزية')
                    ->required()
                    ->rules('required')
                    ->unique(ignoreRecord: true)
                    ->helperText("الإسم مطلوب لعملية تسجيل الدخول")
                    // convert to lowercase
                    ->afterStateHydrated(fn($component, $state) => $component->state(strtolower($state)))
                    ->dehydrateStateUsing(fn($state) => strtolower($state)),
                Forms\Components\Select::make('role')
                    ->label('الوظيفة')
                    ->native(false)
                    ->options(
                        \App\Enums\UserRole::cases()
                            ? collect(\App\Enums\UserRole::cases())->mapWithKeys(function ($case) {
                                // Map enum value to Arabic label
                                return [
                                    $case->value => match ($case->name) {
                                        'ADMIN' => 'ادمن',
                                        'EMPLOYEE' => 'موظف',
                                        default => $case->name,
                                    }
                                ];
                            })->toArray()
                            : []
                    )
                    ->extraAttributes(['class' => 'font-medium'])
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('الباسورد')
                    ->password()
                    ->helperText(function ($component) {
                        if ($component->getModelInstance()->exists) {
                            return 'فى حالة عدم الرغبة فى تعديل الباسورد يرجى ترك الحقل فارغاً';
                        }
                    })
                    ->required(fn($component) => ! $component->getModelInstance()->exists)
                    ->revealable()
                    ->rules(function ($component) {
                        return $component->getModelInstance()->exists
                            ? ['nullable', 'confirmed', 'min:8']
                            : ['required', 'confirmed', 'min:8'];
                    })
                    // Only dehydrate if the field has a value
                    ->dehydrated(fn($state) => filled($state))
                    // Hash the password before saving
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('تـاكـيد الـباسورد')
                    ->password()
                    ->required(fn($component) => ! $component->getModelInstance()->exists)
                    ->revealable()
                    ->rules(function ($component) {
                        return $component->getModelInstance()->exists
                            ? ['required_if:password,*']
                            : ['required'];
                    })
                    // Don't save password_confirmation to database
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null) // prevent clickable row
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الإسم بالعربي')
                    ->searchable()
                    ->fontFamily(FontFamily::Sans)
                    ->color('violet')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('username')
                    ->label('إسم الدخول')
                    ->fontFamily(FontFamily::Sans)
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('الوظيفة')
                    ->formatStateUsing(fn($state, $record) => $record->role->name === 'ADMIN' ? 'ادمن' : 'موظف')
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(fn($record) => match ($record->status) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn($state, $record) => $record->status === 'active' ? 'مفعل' : 'غير مفعل'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date("d-m-Y")
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->button()->color('gray')->size('xs'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->extraAttributes(['class' => 'font-semibold']),
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
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return  Auth::user()->role->value === 'admin';
    }
}
