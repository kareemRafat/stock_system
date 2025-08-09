<div id="delivery-note-area" class="hidden bg-white p-6 rounded-lg shadow-sm ring-1 ring-gray-200 print:block">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start mb-6">
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-primary-600 mb-4">إذن صرف</h1>
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
                <div style="width: 1px; height: auto; border-right: 1px solid rgb(111, 111, 111); margin: 0 2px;">
                </div>

                <!-- Date -->
                <div class="flex items-center flex-col">
                    <span class="mr-1 mb-2">التاريخ:</span>
                    <span>{{ $record->created_at->format('d-m-Y') }}</span>
                </div>

            </div>
            <div class="flex px-4 mt-4 pt-4 pb-2 mb-2 text-sm font-medium justify-end gap-4">
                <p>01030231321</p>
                <span> - </span>
                <p>01030231321</p>
            </div>
        </div>

    </div>
    <!-- Separator -->
    <hr class="my-6 border-gray-200">

    <!-- Bill To Section -->
    <div class="mb-6">
        <h3 class="text-base font-medium text-gray-700 my-4">طبعت اذن الصرف لأمر :</h3>
        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 my-4 flex flex-col sm:flex-row justify-between">
            <p class="font-medium text-gray-700">{{ $record->customer->name ?? '-' }}</p>
            <p class="text-gray-700 text-sm">{{ $record->customer->address ?? '---' }}</p>
        </div>
    </div>
    <div>
        <table class="w-full border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-right py-2 px-4 font-medium text-sm border border-gray-300">مسلسل</th>
                    <th class="text-right py-2 px-4 font-medium text-sm border border-gray-300">المنتج</th>
                    <th class="text-right py-2 px-4 font-medium text-sm border border-gray-300">الكمية</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->items as $item)
                    <tr>
                        <td class="text-right py-2 px-4 text-sm border border-gray-300">
                            {{ $loop->iteration }}
                        </td>
                        <td class="text-right py-2 px-4 text-sm border border-gray-300">
                            {{ $item->product->name ?? '---' }}</td>
                        <td class="text-right py-2 px-4 text-sm border border-gray-300">
                            {{ $item->quantity }}
                            {{ $item->product->unit ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
