<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;


class CustomerWalletPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected static string $view = 'filament.pages.customers.customer-wallet-page';

    public Customer $customer;

    public function mount(int $record): void
    {
        $this->customer = Customer::findOrFail($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->customer->wallet()->getQuery())
            ->emptyStateHeading('لا توجد حركات رصيد للعملاء')
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
                            + $rowLoop->iteration
                    )
                    ->sortable(false)
                    ->searchable(false)
                    ->weight('semibold'),
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
                    ->label('الكمية')
                    ->money('egp')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->using(
                                fn(\Illuminate\Database\Query\Builder $query) =>
                                $query->clone()->selectRaw("
                    SUM(
                        CASE
                            WHEN type = 'debit' THEN -amount
                            WHEN type = 'invoice' THEN -amount
                            WHEN type = 'credit' THEN amount
                            ELSE 0
                        END
                    ) as balance
                ")->value('balance') ?? 0
                            )
                            ->label('الرصيد الكلي')
                            ->money('egp'),
                    ]),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('سحب بالفاتورة')
                    ->default('لايوجد'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات الحركة')
                    ->limit(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date('d-m-Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'credit' => 'إيداع',
                        'debit' => 'سحب',
                        'invoice' => 'فاتورة',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50]);
    }

    public static function getRouteName(?string $panel = null): string
    {
        return static::generateRouteName('wallet', $panel);
    }

    public static function getRoutePath(?string $panel = null): string
    {
        return '/{record}/wallet';
    }

    // heading at the top of the page
    public function getHeading(): string
    {
        return 'حركات رصيد العميل';
    }

    // breadcrumb label (right side of العملاء)
    public function getBreadcrumb(): string
    {
        return 'حركات الرصيد';
    }

    // page title (HTML <title> tag, browser tab)
    public function getTitle(): string
    {
        return 'حركات رصيد العميل';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    return CustomerResource::getUrl('index');
                }),
        ];
    }
}
