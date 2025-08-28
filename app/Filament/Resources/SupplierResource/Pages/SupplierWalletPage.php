<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions;

class SupplierWalletPage extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = SupplierResource::class;

    protected static string $view = 'filament.pages.suppliers.supplier-wallet-page';

    public Supplier $supplier;

    public function mount(int $record): void
    {
        $this->supplier = Supplier::findOrFail($record);
    }

    public static function getResource(): string
    {
        return SupplierResource::class;
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('لا توجد حركات رصيد للمورد')
            ->query($this->supplier->wallet()->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->colors([
                        'success' => 'credit',
                        'danger' => 'debit',
                        'warning' => 'invoice',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'credit' => 'إيداع',
                        'debit' => 'سحب',
                        'invoice' => 'فاتورة',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('egp'),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('فاتورة')
                    ->default('لا يوجد'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date('d-m-Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getHeading(): string
    {
        return 'حركات رصيد المورد';
    }

    public function getBreadcrumb(): string
    {
        return 'رصيد المورد';
    }

    public function getTitle(): string
    {
        return 'رصيد المورد';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => SupplierResource::getUrl('index')),
        ];
    }
}
