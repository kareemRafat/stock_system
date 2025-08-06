<x-filament-panels::page>
    <div class="px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-white p-6 rounded-lg shadow-sm ring-1 ring-gray-200">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start mb-6">
                <div class="mb-4">
                    <h1 class="text-3xl font-bold text-primary-600 mb-4">فاتورة</h1>
                    <div class="text-gray-500 text-sm space-y-1">
                        <p class="text-base font-medium text-gray-700">شركة أحمد حسين</p>
                        <p>لمواد التعبئة والتغليف</p>
                        <p>01016011318</p>
                    </div>
                </div>

                <div class="mt-4 sm:mt-0 sm:text-right">
                    <div class="bg-primary-100 text-primary-800 px-3 py-1.5 rounded-md mb-3 text-sm font-medium">
                        رقم الفاتورة : #{{ $record->invoice_number }}
                    </div>
                    <div class="text-gray-500 text-sm space-y-1 px-3 py-1.5">
                        <p>
                            <span class="font-medium text-gray-700">التاريخ: </span>
                            {{ $record->created_at->format('d-m-Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Separator -->
            <hr class="my-6 border-gray-200">

            <!-- Bill To Section -->
            <div class="mb-6">
                <h3 class="text-base font-medium text-gray-700 my-4">طبعت الفاتورة ل:</h3>
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200 my-4">
                    <p class="font-medium text-gray-700">{{ $record->customer->name ?? '-' }}</p>
                    <p class="text-gray-500 text-sm">{{ $record->customer->address ?? '---' }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <div class="overflow-hidden rounded-md border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-right py-3 px-4 font-medium text-gray-700 text-sm">المنتج</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-700 text-sm">الكمية</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-700 text-sm">السعر</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-700 text-sm">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($record->items as $item)
                                <tr class="border-t border-gray-200">
                                    <td class="py-3 px-4 text-gray-700 text-sm">
                                        {{ $item->product->name ?? '---' }}
                                    </td>
                                    <td class="py-3 px-4 text-center text-gray-500 text-sm">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-500 text-sm">
                                        {{ number_format($item->price, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-medium text-gray-700 text-sm">
                                        {{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-full sm:w-80">
                    <div class="space-y-2">
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm">الإجمالي:</span>
                            <span class="font-medium text-sm">{{ number_format($record->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm">الخصومات:</span>
                            <span class="font-medium text-sm">{{ number_format($record->discount, 2) }}</span>
                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between py-3 bg-primary-100 text-primary-800 px-4 rounded-md">
                            <span class="text-base font-medium">الإجمالي بعد الخصم:</span>
                            <span class="text-base font-medium">{{ number_format($record->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
