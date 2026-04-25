{{-- Show Sales Modal --}}
@php
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
@endphp
@if(request('modal') === 'show')
    @if(!$viewingSales)
        <x-app-modal
            title="Sale Details"
            subtitle="The selected sale record could not be loaded."
            :close-url="$salesBaseUrl"
            max-width="sm"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-100 text-orange-600">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="py-4 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-100">
                    <x-heroicon-o-exclamation-triangle class="h-7 w-7 text-orange-600" />
                </div>
                <h4 class="text-lg font-semibold text-gray-900">Sale record not available</h4>
                <p class="mt-2 text-sm text-gray-500">The selected sale could not be loaded or no longer belongs to this broker account.</p>
                <div class="mt-6">
                    <button
                       type="button"
                       data-sales-modal-close
                       data-close-url="{{ $salesBaseUrl }}"
                       class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                        Back to Sales
                    </button>
                </div>
            </div>
        </x-app-modal>
    @else
        @php
            $buyerName = $viewingSales->buyer?->name ?: 'Walk-in Buyer';
            $buyerContact = $viewingSales->buyer?->contact ?: 'Not provided';
            $paymentProgress = $viewingSales->total_amount > 0
                ? (($viewingSales->paid_amount / (float) $viewingSales->total_amount) * 100)
                : 0;
        @endphp

        <x-app-modal
            title="Sale Details"
            :subtitle="'Sale #' . $viewingSales->id . ' • ' . $viewingSales->sales_date->format('M d, Y')"
            :close-url="$salesBaseUrl"
            max-width="7xl"
            body-class="workspace-popup__body--soft"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-sm">
                    <x-heroicon-o-shopping-cart class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="space-y-8">
                        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                <div class="mb-4 flex items-center">
                                    <div class="mr-3 rounded-lg bg-blue-100 p-2">
                                        <x-heroicon-o-user class="h-5 w-5 text-blue-600" />
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900">Sale Information</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-calendar class="mr-2 h-4 w-4" />
                                            Date
                                        </span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $viewingSales->sales_date->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-user class="mr-2 h-4 w-4" />
                                            Buyer
                                        </span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $buyerName }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-phone class="mr-2 h-4 w-4" />
                                            Contact
                                        </span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $buyerContact }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-flag class="mr-2 h-4 w-4" />
                                            Status
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $salesStatusesWithColorClasses[$viewingSales->status] }}">
                                            {{ $salesStatusesWithDisplayNames[$viewingSales->status] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                <div class="mb-4 flex items-center">
                                    <div class="mr-3 rounded-lg bg-green-100 p-2">
                                        <x-heroicon-o-currency-dollar class="h-5 w-5 text-green-600" />
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900">Financial Summary</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-banknotes class="mr-2 h-4 w-4" />
                                            Total Amount
                                        </span>
                                        <span class="text-lg font-bold text-gray-900">PHP {{ number_format((float) $viewingSales->total_amount, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-check-circle class="mr-2 h-4 w-4" />
                                            Paid Amount
                                        </span>
                                        <span class="text-lg font-bold text-green-600">PHP {{ number_format($viewingSales->paid_amount, 2) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="flex items-center text-sm text-gray-600">
                                            <x-heroicon-o-clock class="mr-2 h-4 w-4" />
                                            Remaining
                                        </span>
                                        <span class="text-lg font-bold {{ $viewingSales->remaining_amount > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                            PHP {{ number_format($viewingSales->remaining_amount, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                <div class="mb-4 flex items-center">
                                    <div class="mr-3 rounded-lg bg-purple-100 p-2">
                                        <x-heroicon-o-chart-bar class="h-5 w-5 text-purple-600" />
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900">Payment Progress</h4>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Payment Progress</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ number_format($paymentProgress, 1) }}%</span>
                                    </div>
                                    <div class="h-3 w-full rounded-full bg-gray-200">
                                        <div class="h-3 rounded-full bg-gradient-to-r from-green-500 to-green-600 transition-all duration-300"
                                             style="width: {{ min(100, $paymentProgress) }}%"></div>
                                    </div>
                                    <div class="text-center">
                                        @if($viewingSales->status === \App\Constants\SalesStatusConstant::PAID)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                <x-heroicon-o-check-circle class="mr-1 h-4 w-4" />
                                                Fully Paid
                                            </span>
                                        @elseif($viewingSales->status === \App\Constants\SalesStatusConstant::PARTIALLY_PAID)
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                                                <x-heroicon-o-clock class="mr-1 h-4 w-4" />
                                                Partially Paid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                                <x-heroicon-o-exclamation-triangle class="mr-1 h-4 w-4" />
                                                Pending Payment
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <div class="flex items-center">
                                    <div class="mr-3 rounded-lg bg-indigo-100 p-2">
                                        <x-heroicon-o-archive-box class="h-5 w-5 text-indigo-600" />
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900">Commodities Sold</h4>
                                    <span class="ml-2 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                        {{ $viewingSales->salesDetails->count() }} fish boxes
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fish Box</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fish Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Purchase Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Price per Box</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Discount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sub Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @forelse($viewingSales->salesDetails as $detail)
                                            <tr class="transition-colors hover:bg-gray-50">
                                                <td class="whitespace-nowrap px-6 py-4">
                                                    <div class="flex items-center">
                                                        <div class="mr-3 rounded-lg bg-blue-100 p-1.5">
                                                            <x-heroicon-o-archive-box class="h-4 w-4 text-blue-600" />
                                                        </div>
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{ $detail->fishBoxPurchase?->fishBox?->name ?? ('Fish Box #' . $detail->fish_box_purchase_id) }}
                                                            </div>
                                                            @if($detail->fishBoxPurchase?->fishBox?->qr_code)
                                                                <div class="text-xs text-gray-500">{{ $detail->fishBoxPurchase->fishBox->qr_code }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                                    {{ $detail->fishBoxPurchase?->fishType?->name ?? 'Unknown fish name' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {{ $detail->fishBoxPurchase?->purchase_date?->format('M d, Y') ?? '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                                    PHP {{ number_format((float) $detail->unit_price, 2) }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    PHP {{ number_format((float) $detail->discount, 2) }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">
                                                    PHP {{ number_format((float) $detail->sub_total, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-6 py-12 text-center">
                                                    <div class="flex flex-col items-center">
                                                        <x-heroicon-o-archive-box class="mb-2 h-12 w-12 text-gray-400" />
                                                        <p class="text-sm text-gray-500">No commodities found for this sale.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if($viewingSales->salesPayments->count() > 0)
                            <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="mr-3 rounded-lg bg-emerald-100 p-2">
                                            <x-heroicon-o-credit-card class="h-5 w-5 text-emerald-600" />
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-900">Payment History</h4>
                                        <span class="ml-2 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                            {{ $viewingSales->salesPayments->count() }} payments
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Method</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                                @unless($brokerViewReadOnly)
                                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                                @endunless
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @foreach($viewingSales->salesPayments as $payment)
                                                <tr class="transition-colors hover:bg-gray-50">
                                                    <td class="whitespace-nowrap px-6 py-4">
                                                        <div class="flex items-center">
                                                            <div class="mr-3 rounded-lg bg-gray-100 p-1.5">
                                                                <x-heroicon-o-calendar class="h-4 w-4 text-gray-600" />
                                                            </div>
                                                            <div class="text-sm font-medium text-gray-900">{{ $payment->payment_date->format('M d, Y') }}</div>
                                                        </div>
                                                    </td>
                                                    <td class="whitespace-nowrap px-6 py-4">
                                                        <div class="text-lg font-bold text-green-600">PHP {{ number_format((float) $payment->paid_amount, 2) }}</div>
                                                    </td>
                                                    <td class="whitespace-nowrap px-6 py-4">
                                                        <div class="flex items-center">
                                                            <div class="mr-3 rounded-lg bg-blue-100 p-1.5">
                                                                <x-heroicon-o-credit-card class="h-4 w-4 text-blue-600" />
                                                            </div>
                                                            <div class="text-sm font-medium text-gray-900">{{ $payment->payment_method }}</div>
                                                        </div>
                                                    </td>
                                                    <td class="whitespace-nowrap px-6 py-4">
                                                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                            <x-heroicon-o-check-circle class="mr-1 h-3 w-3" />
                                                            Recorded
                                                        </span>
                                                    </td>
                                                    @unless($brokerViewReadOnly)
                                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                                            <form action="{{ route('broker.sales-payments.destroy', $payment->id) }}"
                                                                  method="POST"
                                                                  class="inline"
                                                                  data-swal="delete">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="rounded-lg p-1 text-red-600 transition-colors hover:bg-red-50 hover:text-red-800"
                                                                        title="Delete Payment">
                                                                    <x-heroicon-o-trash class="h-4 w-4" />
                                                                </button>
                                                            </form>
                                                        </td>
                                                    @endunless
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="mb-8">
                                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                        <x-heroicon-o-credit-card class="h-8 w-8 text-gray-400" />
                                    </div>
                                    <h4 class="mb-2 text-lg font-medium text-gray-900">No Payment History</h4>
                                    <p class="mb-4 text-gray-500">This sale does not have any payment records yet.</p>
                                    @unless($brokerViewReadOnly)
                                        <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'payment', 'sale' => $viewingSales->id])) }}"
                                           data-sales-modal-link
                                           class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
                                            <x-heroicon-o-plus class="mr-2 h-4 w-4" />
                                            Add Payment
                                        </a>
                                    @endunless
                                </div>
                            </div>
                        @endif
                    </div>

            <div class="flex flex-col gap-4 border-t border-gray-200 pt-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center space-x-4">
                    @if(!$brokerViewReadOnly && $viewingSales->status !== \App\Constants\SalesStatusConstant::PAID)
                        <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'payment', 'sale' => $viewingSales->id])) }}"
                           data-sales-modal-link
                           class="inline-flex items-center justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700">
                            <x-heroicon-o-plus class="mr-2 h-4 w-4" />
                            Add Payment
                        </a>
                        <span class="text-sm text-gray-500">
                            Outstanding Balance: <span class="font-semibold text-orange-600">PHP {{ number_format($viewingSales->remaining_amount, 2) }}</span>
                        </span>
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                       type="button"
                       data-sales-modal-close
                       data-close-url="{{ $salesBaseUrl }}"
                        class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                        Close
                    </button>
                    @unless($brokerViewReadOnly)
                        <a href="{{ route('broker.sales.sales', array_merge($salesBaseQuery, ['modal' => 'edit', 'edit' => $viewingSales->id])) }}"
                           data-sales-modal-link
                           class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700 sm:w-auto">
                            <x-heroicon-o-pencil-square class="mr-2 h-4 w-4" />
                            Edit Sale
                        </a>
                    @endunless
                </div>
            </div>
        </x-app-modal>
    @endif
@endif
