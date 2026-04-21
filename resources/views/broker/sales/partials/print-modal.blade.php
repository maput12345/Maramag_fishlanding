{{-- Print Receipt Modal --}}
@if(request('modal') === 'print')
    @if($printingSales)
        <x-app-modal
            title="Print Receipt"
            subtitle="Review the final sale summary and print a clean receipt."
            :close-url="$salesBaseUrl"
            max-width="lg"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-sm">
                    <x-heroicon-o-printer class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div id="receipt-content" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mx-auto max-w-md bg-white">
                            {{-- Company Header --}}
                            <div class="text-center border-b border-gray-200 pb-4 mb-4">
                                <h1 class="text-2xl font-bold text-gray-900">{{ $printingSales->broker->name }}</h1>
                                <p class="text-sm text-gray-600">{{ $printingSales->broker->stall_name }}</p>
                                <p class="text-xs text-gray-500">Receipt #{{ $printingSales->id }}</p>
                            </div>

                            {{-- Sale Information --}}
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-medium">{{ $printingSales->sales_date->format('M d, Y g:i A') }}</span>
                                </div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Buyer:</span>
                                    <span class="font-medium">{{ $printingSales->buyer_name }}</span>
                                </div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Contact:</span>
                                    <span class="font-medium">{{ $printingSales->buyer_contact }}</span>
                                </div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="font-medium {{ $salesStatusesWithColorClasses[$printingSales->status] }}">
                                        {{ $salesStatusesWithDisplayNames[$printingSales->status] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Items --}}
                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">Items Sold</h3>
                                <div class="space-y-3">
                                    @foreach($printingSales->salesDetails as $detail)
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900">{{ $detail->item }}</div>
                                                    @if($detail->item_description)
                                                        <div class="text-xs text-gray-500 mt-1">{{ $detail->item_description }}</div>
                                                    @endif
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-semibold text-gray-900">₱{{ number_format($detail->sub_total, 2) }}</div>
                                                    <div class="text-xs text-gray-500">{{ $detail->quantity }} × ₱{{ number_format($detail->unit_price, 2) }}</div>
                                                </div>
                                            </div>

                                            {{-- Fish Boxes --}}
                                            @if(is_array($detail->box_id) && count($detail->box_id) > 0)
                                                <div class="mt-2">
                                                    <div class="text-xs text-gray-600 mb-1">Fish Boxes:</div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($detail->box_id as $boxId)
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                {{ $detail->fishBox?->name ?? ('Fish Box #' . $boxId) }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Payment History --}}
                            @if($printingSales->salesPayments->count() > 0)
                                <div class="border-t border-gray-200 pt-4 mb-4">
                                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Payment History</h3>
                                    <div class="space-y-2">
                                        @foreach($printingSales->salesPayments as $payment)
                                            <div class="flex justify-between items-center text-xs">
                                                <div>
                                                    <div class="font-medium">{{ $payment->payment_date->format('M d, Y') }}</div>
                                                    <div class="text-gray-500">{{ $payment->payment_method }}</div>
                                                </div>
                                                <div class="font-semibold text-green-600">₱{{ number_format($payment->paid_amount, 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Payment Summary --}}
                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total Amount:</span>
                                        <span class="font-semibold">₱{{ number_format($printingSales->total_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Paid Amount:</span>
                                        <span class="font-semibold text-green-600">₱{{ number_format($printingSales->paid_amount, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm border-t pt-2">
                                        <span class="text-gray-600 font-semibold">Remaining Balance:</span>
                                        <span class="font-bold text-orange-600">₱{{ number_format($printingSales->remaining_amount, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Remarks --}}
                            @if($printingSales->remarks)
                                <div class="border-t border-gray-200 pt-4 mb-4">
                                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Remarks</h3>
                                    <p class="text-xs text-gray-600">{{ $printingSales->remarks }}</p>
                                </div>
                            @endif

                            {{-- Footer --}}
                            <div class="border-t border-gray-200 pt-4 text-center">
                                <p class="text-xs text-gray-500">Thank you for your business!</p>
                                <p class="text-xs text-gray-400 mt-1">Generated on {{ now()->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                <button
                   type="button"
                   data-sales-modal-close
                   data-close-url="{{ $salesBaseUrl }}"
                   class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                    Close
                </button>
                <button onclick="printReceiptBroker()"
                        class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700 sm:w-auto">
                    <x-heroicon-o-printer class="mr-2 h-4 w-4" />
                    Print Receipt
                </button>
            </div>
        </x-app-modal>
    @else
        <x-app-modal
            title="Sale Not Found"
            subtitle="The selected sale could not be prepared for printing."
            :close-url="$salesBaseUrl"
            max-width="sm"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="py-4 text-center">
                <p class="mb-6 text-sm text-gray-500">The sale you're trying to print could not be found or you don't have permission to access it.</p>
                <button
                   type="button"
                   data-sales-modal-close
                   data-close-url="{{ $salesBaseUrl }}"
                   class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                    <x-heroicon-o-arrow-left class="mr-2 h-4 w-4" />
                    Back to Sales
                </button>
            </div>
        </x-app-modal>
    @endif
@endif

