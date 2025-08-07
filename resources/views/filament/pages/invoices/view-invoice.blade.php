<x-filament-panels::page>
    <div class="py-4">
        <!-- Print Button -->
        <div class="flex justify-end mb-4 no-print">
            <button onclick="printInvoice()" class="...">🖨️ طباعة</button>
        </div>
        <div id="print-area" class="bg-white p-6 rounded-lg shadow-sm ring-1 ring-gray-200">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start mb-6">
                <div class="mb-4">
                    <h1 class="text-3xl font-bold text-primary-600 mb-4">فاتورة</h1>
                    <div class="text-gray-500 text-sm space-y-1">
                        <p class="text-base font-medium text-gray-700">شركة أحمد حسين</p>
                        <p>لمواد التعبئة والتغليف</p>
                    </div>
                </div>

                <div class="mt-4 sm:mt-0 sm:text-right">
                    <div
                        class="flex items-stretch bg-primary-100 text-primary-800 px-4 py-1.5 rounded-md mb-3 text-sm font-medium justify-end gap-4">

                        <!-- Invoice Number -->
                        <div class="flex items-center flex-col">
                            <span class="mb-2">رقم الفاتورة :</span>
                            <span> #{{ $record->invoice_number }}</span>
                        </div>

                        <!-- Vertical Separator -->
                        <div class="w-px bg-gray-400 mx-2"></div>

                        <!-- Date -->
                        <div class="flex items-center text-gray-700 flex-col">
                            <span class="mr-1 mb-2">التاريخ:</span>
                            <span>{{ $record->created_at->format('d-m-Y') }}</span>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Separator -->
            <hr class="my-6 border-gray-200">

            <!-- Bill To Section -->
            <div class="mb-6">
                <h3 class="text-base font-medium text-gray-700 my-4">طبعت الفاتورة لأمر :</h3>
                <div class="bg-gray-50 p-4 rounded-md border border-gray-200 my-4 flex flex-col sm:flex-row justify-between">
                    <p class="font-medium text-gray-700">{{ $record->customer->name ?? '-' }}</p>
                    <p class="text-gray-700 text-sm">{{ $record->customer->address ?? '---' }}</p>
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
                                <th class="text-right py-3 px-4 font-medium text-gray-700 text-sm">الخصم</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-700 text-sm">السعر </th>
                                <th class="text-right py-3 px-4 font-medium text-gray-700 text-sm">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalBeforeSale = 0;
                                $discounts = 0;
                            @endphp
                            @foreach ($record->items as $item)
                                @php
                                    $totalBeforeSale += $item->product->price;
                                    $discounts +=
                                        $item->product->discount > 0
                                            ? ($item->product->price * $item->quantity * $item->product->discount) / 100
                                            : 0;
                                @endphp
                                <tr class="border-t border-gray-200">
                                    <td class="py-3 px-4 text-gray-700 text-sm">
                                        {{ $item->product->name ?? '---' }}
                                    </td>
                                    <td class="py-3 px-4 text-center text-gray-500 text-sm">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-500 text-sm">
                                        {{ $item->product->discount > 0 ? $item->product->discount . ' %' : '---' }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-500 text-sm">
                                        {{ number_format($item->product->price, 2) }}
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
                            <span class="font-medium text-sm">{{ number_format($totalBeforeSale, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm">الخصومات:</span>
                            <span class="font-medium text-sm">{{ number_format($discounts, 2) }}</span>
                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between py-3 bg-primary-100 text-primary-800 px-4 rounded-md">
                            <span class="text-base font-medium">الإجمالي بعد الخصم:</span>
                            <span class="text-base font-medium">{{ number_format($record->total_amount, 2) }} ج.م
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function printInvoice() {
            const printContents = document.getElementById('print-area').innerHTML;
            const originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
    </script>

    <style>
        @media print {

            /* Remove default margins */
            body,
            html {
                margin: 0;
                padding: 0;
                direction: rtl;
            }

            /* Force print area to take full width */
            #print-area {
                margin: 0 !important;
                padding: 0 !important;
                width: 100%;
                direction: rtl;
            }

            /* Remove screen-only elements */
            .no-print {
                display: none !important;
            }

            /* Avoid shadows/rings on print */
            .shadow,
            .ring,
            .ring-1,
            .ring-gray-200 {
                box-shadow: none !important;
                border: none !important;
            }

            /* Adjust table if needed */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            td,
            th {
                padding: 6px 8px !important;
            }
        }
    </style>
</x-filament-panels::page>
