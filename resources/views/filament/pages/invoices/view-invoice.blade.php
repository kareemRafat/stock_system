<x-filament-panels::page>
    {{-- {{ dd($this->record->items->first()->relationLoaded('product')) }} --}}
    {{-- @vite('resources/css/app.css') --}}
    {{-- @vite('resources/js/app.js') --}}
    <div class="py-4">
        <!-- Print Buttons Section -->
        <div class="flex justify-end mb-4 no-print gap-4 ">
            <!-- Print Button for ezn el saft -->
            <button onclick="printDeliveryNote()"
                class="flex items-center text-sm font-semibold text-white px-4 py-1 rounded-md shadow hover:bg-primary-700 transition duration-200 gap-2"
                style="background-color: #0f766e;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 mx-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v12a2 2 0 01-2 2z" />
                </svg>
                <span class="mx-4">طباعة إذن الصرف</span>
            </button>
            <!-- Print Button -->
            <button onclick="printInvoice()"
                class="flex items-center text-sm font-semibold text-white px-4 py-2 rounded-md shadow hover:bg-primary-700 transition duration-200 gap-2"
                style="background-color: #6860ff;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 mx-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                </svg>
                <span class="mx-4">طباعة الفاتورة</span>
            </button>

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
                            <span> # {{ $this->record->invoice_number }}</span>
                        </div>

                        <!-- Vertical Separator -->
                        <div
                            style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                        </div>

                        <!-- Date -->
                        <div class="flex items-center flex-col">
                            <span class="mr-1 mb-2">التاريخ:</span>
                            <span>{{ $this->record->createdDate }}</span>
                            <span dir="ltr">{{ $this->record->createdTime }}</span>
                        </div>

                    </div>
                    <div class="flex px-4 mt-4 pt-4 pb-2 mb-2 text-sm font-medium justify-end gap-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 font-bold">
                            <path fill-rule="evenodd"
                                d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                                clip-rule="evenodd" />
                        </svg>
                        <p>01030231321</p>
                        <span> - </span>
                        <p>01030231321</p>
                    </div>
                </div>

            </div>

            <!-- Separator -->
            <hr class="my-6 border-gray-900">

            <!-- Bill To Section -->
            <div class="mb-6">
                <h3 class="text-base font-medium text-gray-700 my-4">طبعت الفاتورة لأمر :</h3>
                <div
                    class="bg-gray-50 p-4 rounded-md border border-gray-900 my-4 flex flex-col sm:flex-row justify-between">
                    <p class="font-medium text-gray-700">{{ $this->record->customer->name ?? '-' }}</p>
                    <p class="text-gray-700 text-sm">{{ $this->record->customer->address ?? '---' }}</p>
                </div>
            </div>

            <!-- Items Table for Invoice -->
            <div class="mb-6 ">
                <div class="overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-700 text-sm border border-gray-900">
                                    مسلسل</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-700 text-sm border border-gray-900">
                                    المنتج</th>
                                <th
                                    class="text-center py-2 px-4 font-medium text-gray-700 text-sm border border-gray-900">
                                    الكمية</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-700 text-sm border border-gray-900">
                                    الخصم</th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-700 text-sm border border-gray-900">
                                    السعر </th>
                                <th
                                    class="text-right py-2 px-4 font-medium text-gray-700 text-sm border border-gray-900">
                                    الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalBeforeSale = 0;
                                $totalDiscounts = 0;
                            @endphp
                            @foreach ($this->record->items as $item)
                                @php
                                    $totalBeforeSale += $item->product->price * $item->quantity;
                                    $totalDiscounts +=
                                        $item->product->discount > 0
                                            ? ($item->product->price * $item->quantity * $item->product->discount) / 100
                                            : 0;
                                @endphp
                                <tr class="border-t border-gray-900">
                                    <td class="py-2 px-4 text-right text-gray-500 text-sm border border-gray-900">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="py-2 px-4 text-gray-700 text-sm border border-gray-900">
                                        {{ $item->product->name ?? '---' }}
                                    </td>
                                    <td class="py-2 px-4 text-center text-gray-500 text-sm border border-gray-900">
                                        {{ $item->quantity }} {{ $item->product->unit ?? '---' }}
                                    </td>
                                    <td class="py-2 px-4 text-right text-gray-500 text-sm border border-gray-900">
                                        {{ $item->product->discount > 0 ? $item->product->discount . ' %' : '---' }}
                                    </td>
                                    <td class="py-2 px-4 text-right text-gray-500 text-sm border border-gray-900">
                                        {{ number_format($item->product->price, 2) }}
                                    </td>
                                    <td
                                        class="py-2 px-4 text-right font-medium text-gray-700 text-sm border border-gray-900">
                                        {{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Items Table for Ezn el sarf -->
            @include('filament.pages.invoices.delivery-note')

            <!-- Totals -->
            <div class="flex justify-end mt-2 font-semibold">
                <div class="w-full sm:w-80">
                    <div class="space-y-2">
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm">الإجمالي:</span>
                            <span class="font-medium text-sm">{{ number_format($totalBeforeSale, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-2">
                            <span class="text-sm">الخصومات:</span>
                            <span class="font-medium text-sm">{{ number_format($totalDiscounts, 2) }}</span>
                        </div>
                        <hr class="border-gray-900">
                        <div class="flex justify-between py-3 px-4 rounded-md">
                            <span class="text-base font-medium">الإجمالي بعد الخصم:</span>
                            <span class="text-base font-medium">
                                {{ number_format(
                                    $this->record->total_amount == 0 ? $totalBeforeSale - $totalDiscounts : $this->record->total_amount
                                , 2) }} ج.م
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
        }

        function printDeliveryNote() {
            const deliveryNoteArea = document.getElementById('delivery-note-area');

            if (!deliveryNoteArea) {
                alert('منطقة إذن الصرف غير موجودة');
                return;
            }

            const printContents = deliveryNoteArea.innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;

        }
    </script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

        }
    </style>
</x-filament-panels::page>
