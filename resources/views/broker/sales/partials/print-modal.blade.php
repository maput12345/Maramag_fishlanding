{{-- Print Receipt Modal --}}
@if(request('modal') === 'print')
    @if($printingSales)
        @php
            $receiptLineItems = $printingSales->salesDetails
                ->groupBy(function ($detail) {
                    return implode('|', [
                        trim(mb_strtolower($detail->item ?? '')),
                        number_format((float) $detail->unit_price, 2, '.', ''),
                    ]);
                })
                ->map(function ($details) {
                    $firstDetail = $details->first();

                    return [
                        'item' => $firstDetail?->item ?? '',
                        'item_description' => $details->pluck('item_description')->filter()->unique()->implode(' / '),
                        'unit_price' => (float) ($firstDetail?->unit_price ?? 0),
                        'quantity' => (int) $details->sum(fn ($detail) => (int) $detail->quantity),
                        'sub_total' => (float) $details->sum(fn ($detail) => (float) $detail->sub_total),
                        'fish_boxes' => $details
                            ->flatMap(fn ($detail) => $detail->fishBoxes())
                            ->filter()
                            ->unique('id')
                            ->values(),
                    ];
                })
                ->values();
        @endphp
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

            <div
                id="receipt-content"
                data-watermark-logo-url="{{ asset('image/logo.png') }}"
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="mx-auto max-w-md bg-white">
                    {{-- Company Header --}}
                    <div class="mb-4 border-b border-gray-200 pb-4 text-center">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $printingSales->broker->name }}</h1>
                        <p class="text-sm text-gray-600">{{ $printingSales->broker->stall_name }}</p>
                        <p class="text-xs text-gray-500">Receipt #{{ $printingSales->id }}</p>
                    </div>

                    {{-- Sale Information --}}
                    <div class="mb-4">
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-gray-600">Date:</span>
                            <span class="font-medium">{{ $printingSales->sales_date->format('M d, Y g:i A') }}</span>
                        </div>
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-gray-600">Buyer:</span>
                            <span class="font-medium">{{ $printingSales->buyer_name }}</span>
                        </div>
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-gray-600">Contact:</span>
                            <span class="font-medium">{{ $printingSales->buyer_contact }}</span>
                        </div>
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium {{ $salesStatusesWithColorClasses[$printingSales->status] }}">
                                {{ $salesStatusesWithDisplayNames[$printingSales->status] }}
                            </span>
                        </div>
                    </div>

                    {{-- Commodities --}}
                    <div class="mb-4 border-t border-gray-200 pt-4">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Commodities Sold</h3>
                        <div class="space-y-3">
                            @foreach($receiptLineItems as $lineItem)
                                <div class="rounded-lg bg-gray-50 p-3">
                                    <div class="mb-2 flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">{{ $lineItem['item'] }}</div>
                                            @if($lineItem['item_description'])
                                                <div class="mt-1 text-xs text-gray-500">{{ $lineItem['item_description'] }}</div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-900">PHP {{ number_format($lineItem['sub_total'], 2) }}</div>
                                            <div class="text-xs text-gray-500">{{ $lineItem['quantity'] }} x PHP {{ number_format($lineItem['unit_price'], 2) }}</div>
                                        </div>
                                    </div>

                                    @if($lineItem['fish_boxes']->isNotEmpty())
                                        <div class="mt-2">
                                            <div class="mb-1 text-xs text-gray-600">Fish Boxes:</div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($lineItem['fish_boxes'] as $fishBox)
                                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                                        {{ $fishBox->name }}
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
                        <div class="mb-4 border-t border-gray-200 pt-4">
                            <h3 class="mb-3 text-sm font-semibold text-gray-900">Payment History</h3>
                            <div class="space-y-2">
                                @foreach($printingSales->salesPayments as $payment)
                                    <div class="flex items-center justify-between text-xs">
                                        <div>
                                            <div class="font-medium">{{ $payment->payment_date->format('M d, Y') }}</div>
                                            <div class="text-gray-500">{{ $payment->payment_method }}</div>
                                        </div>
                                        <div class="font-semibold text-green-600">PHP {{ number_format($payment->paid_amount, 2) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Payment Summary --}}
                    <div class="mb-4 border-t border-gray-200 pt-4">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Amount:</span>
                                <span class="font-semibold">PHP {{ number_format($printingSales->total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Paid Amount:</span>
                                <span class="font-semibold text-green-600">PHP {{ number_format($printingSales->paid_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-2 text-sm">
                                <span class="font-semibold text-gray-600">Remaining Balance:</span>
                                <span class="font-bold text-orange-600">PHP {{ number_format($printingSales->remaining_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    @if($printingSales->remarks)
                        <div class="mb-4 border-t border-gray-200 pt-4">
                            <h3 class="mb-2 text-sm font-semibold text-gray-900">Remarks</h3>
                            <p class="text-xs text-gray-600">{{ $printingSales->remarks }}</p>
                        </div>
                    @endif

                    {{-- Footer --}}
                    <div class="border-t border-gray-200 pt-4 text-center">
                        <p class="text-xs text-gray-500">Thank you for purchasing!</p>
                        <p class="mt-1 text-xs text-gray-400">Generated on {{ now()->format('M d, Y g:i A') }}</p>
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
