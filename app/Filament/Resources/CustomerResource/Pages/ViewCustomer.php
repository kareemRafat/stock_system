<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use Filament\Actions;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\Facades\Session;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\CustomerResource;
use Filament\Infolists\Components\TextEntry;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('المعلومات الأساسية')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('اسم العميل')
                                    ->weight('bold')
                                    ->size('md')
                                    ->icon('heroicon-o-user')
                                    ->color('primary'),

                                TextEntry::make('phone')
                                    ->label('رقم الهاتف')
                                    ->copyable()
                                    ->copyMessage('تم نسخ رقم الهاتف')
                                    ->placeholder('لايوجد')
                                    ->icon('heroicon-o-phone')
                                    ->color('orange')
                                    ->weight('bold'),

                                TextEntry::make('phone2')
                                    ->label('رقم احتياطي')
                                    ->copyable()
                                    ->copyMessage('تم نسخ الرقم الاحتياطي')
                                    ->placeholder('لايوجد')
                                    ->icon('heroicon-o-phone')
                                    ->color('warning'),

                                TextEntry::make('balance')
                                    ->label('رصيد العميل')
                                    ->placeholder('لايوجد')
                                    ->icon('heroicon-o-banknotes')
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
                                    ->weight('bold')
                            ]),
                    ])
                    ->collapsible(),

                Section::make('معلومات العنوان')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('address')
                                    ->label('العنوان')
                                    ->placeholder('لايوجد')
                                    ->columnSpanFull()
                                    ->size('md')
                                    ->weight('bold')
                                    ->color('indigo')
                                    ->icon('heroicon-o-map-pin')
                                    ->copyable(),

                                TextEntry::make('city')
                                    ->label('المدينة')
                                    ->weight('semibold')
                                    ->placeholder('لايوجد')
                                    ->icon('heroicon-o-building-office'),

                                TextEntry::make('governorate')
                                    ->label('المحافظة')
                                    ->placeholder('لايوجد')
                                    ->weight('semibold')
                                    ->icon('heroicon-o-globe-alt'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public function mount($record): void
    {
        parent::mount($record);

        // Save the previous URL to the session
        Session::put('previous_url', url()->previous());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->hidden(fn() => !Auth::user() || Auth::user()->role->value !== 'admin')
                ->extraAttributes(['class' => 'font-semibold']),
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => Session::get('previous_url') ?? CustomerResource::getUrl('index')),
        ];
    }
}
