@php
    $brokerViewReadOnly = auth()->check() && auth()->user()->isAdmin()
        ? \App\Models\Broker::isAdminBrokerViewReadOnly(auth()->user())
        : false;
    $breadcrumbs = [
        ['title' => 'Buyer Ledger'],
    ];

    $selectedSalesTotal = $selectedBuyerSales->sum(fn ($sale) => (float) $sale->total_amount);
    $selectedPaidTotal = $selectedBuyerSales->sum(fn ($sale) => (float) $sale->paid_amount);
    $selectedBalanceTotal = $selectedBuyerSales->sum(fn ($sale) => (float) $sale->remaining_amount);
    $printReceiptScriptUrl = asset('js/print-receipt.js') . '?v=' . filemtime(public_path('js/print-receipt.js'));
    $buyerLedgerLineItems = function ($sale) {
        return $sale->salesDetails
            ->groupBy(function ($detail) {
                return implode('|', [
                    trim(mb_strtolower($detail->item ?? '')),
                    number_format((float) $detail->unit_price, 2, '.', ''),
                ]);
            })
            ->map(function ($details) {
                $firstDetail = $details->first();

                return [
                    'item' => $firstDetail?->item ?: 'Unassigned item',
                    'unit_price' => (float) ($firstDetail?->unit_price ?? 0),
                    'quantity' => (int) $details->sum(fn ($detail) => (int) $detail->quantity),
                    'sub_total' => (float) $details->sum(fn ($detail) => (float) $detail->sub_total),
                    'fish_boxes' => $details
                        ->flatMap(fn ($detail) => $detail->fishBoxes())
                        ->filter()
                        ->unique('id')
                        ->map(fn ($fishBox) => $fishBox->name)
                        ->values(),
                ];
            })
            ->values();
    };
@endphp

@extends('layouts.broker')

@section('content')
<div class="w-full content-spacing">
    <div class="mb-6 rounded-xl bg-white p-4 shadow-lg">
        <form method="GET" action="{{ route('broker.buyers.index') }}">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="buyer_ledger_search" class="mb-1 block text-sm font-medium text-gray-700">Search Buyer</label>
                    <div class="relative">
                        <input type="text"
                               id="buyer_ledger_search"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search by buyer name or contact..."
                               class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>
                <a href="{{ route('broker.buyers.index') }}" class="btn-clear w-full sm:w-auto">Clear</a>
                <button type="submit" class="btn-search w-full sm:w-auto">Search</button>
                @if(!$brokerViewReadOnly)
                    <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'modal' => 'create'])) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800 sm:w-auto">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        Add Buyer
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div>
        <section class="overflow-hidden rounded-xl bg-white shadow-lg">
            <div class="border-b border-gray-200 px-4 py-3">
                <h2 class="text-base font-semibold text-gray-900">Buyer Balances</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Buyer</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Transactions</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total Sales</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Paid</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Purchase</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($buyers as $buyer)
                            <tr class="{{ $selectedBuyer && (int) $selectedBuyer->id === (int) $buyer->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-900">{{ $buyer->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $buyer->contact ?: 'No contact' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-gray-900">{{ number_format($buyer->transactions_count) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-gray-900">₱{{ number_format((float) $buyer->total_sales, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-green-700">₱{{ number_format((float) $buyer->total_paid, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold tabular-nums {{ (float) $buyer->balance > 0 ? 'text-orange-600' : 'text-green-700' }}">
                                    ₱{{ number_format((float) $buyer->balance, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900">
                                    {{ $buyer->last_purchase_date ? \Illuminate\Support\Carbon::parse($buyer->last_purchase_date)->format('M d, Y') : 'No sales' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => $buyer->id])) }}"
                                           class="text-green-600 transition-colors hover:text-green-900"
                                           title="View buyer ledger">
                                            <x-heroicon-o-eye class="h-5 w-5" />
                                        </a>
                                        @if(!$brokerViewReadOnly)
                                            <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => $buyer->id, 'modal' => 'edit'])) }}"
                                               class="text-slate-600 transition-colors hover:text-slate-900"
                                               title="Edit buyer details">
                                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                                            </a>
                                        @endif
                                        @if(!$brokerViewReadOnly && (float) $buyer->balance > 0)
                                            <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => $buyer->id, 'modal' => 'payment'])) }}"
                                               class="text-blue-600 transition-colors hover:text-blue-900"
                                               title="Add buyer balance payment">
                                                <x-heroicon-o-currency-dollar class="h-5 w-5" />
                                            </a>
                                        @endif
                                        @if($buyer->transactions_count > 0)
                                            <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => $buyer->id, 'modal' => 'print'])) }}"
                                               class="text-purple-600 transition-colors hover:text-purple-900"
                                               title="Print buyer ledger statement">
                                                <x-heroicon-o-printer class="h-5 w-5" />
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No buyers matched the current search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-4 py-4">
                {{ $buyers->appends(array_filter(['search' => $search]))->links('components.pagination') }}
            </div>
        </section>
    </div>

    @if($selectedBuyer && !in_array(request('modal'), ['edit', 'payment', 'print'], true))
        <x-app-modal
            title="Buyer Ledger"
            :subtitle="$selectedBuyer->name . ' · ' . ($selectedBuyer->contact ?: 'No contact number')"
            :close-url="route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page')]))"
            max-width="7xl"
            body-class="workspace-popup__body--soft"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm">
                    <x-heroicon-o-user-group class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center">
                                <div class="mr-3 rounded-lg bg-blue-100 p-2">
                                    <x-heroicon-o-user class="h-5 w-5 text-blue-600" />
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">Buyer Profile</h4>
                            </div>
                            @if(!$brokerViewReadOnly)
                                <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => $selectedBuyer->id, 'modal' => 'edit'])) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                    Edit
                                </a>
                            @endif
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                <span class="text-sm text-gray-600">Name</span>
                                <span class="text-right text-sm font-semibold text-gray-900">{{ $selectedBuyer->name }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                <span class="text-sm text-gray-600">Contact</span>
                                <span class="text-right text-sm font-semibold text-gray-900">{{ $selectedBuyer->contact ?: 'No contact number' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-600">Transactions</span>
                                <span class="text-right text-sm font-semibold text-gray-900">{{ number_format($selectedBuyerSales->count()) }}</span>
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
                                <span class="text-sm text-gray-600">Total Sales</span>
                                <span class="text-right text-lg font-bold tabular-nums text-gray-900">₱{{ number_format($selectedSalesTotal, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                <span class="text-sm text-gray-600">Paid</span>
                                <span class="text-right text-lg font-bold tabular-nums text-green-600">₱{{ number_format($selectedPaidTotal, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-gray-600">Balance</span>
                                <span class="text-right text-lg font-bold tabular-nums {{ $selectedBalanceTotal > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                    ₱{{ number_format($selectedBalanceTotal, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center">
                            <div class="mr-3 rounded-lg {{ $selectedBalanceTotal > 0 ? 'bg-orange-100' : 'bg-green-100' }} p-2">
                                @if($selectedBalanceTotal > 0)
                                    <x-heroicon-o-clock class="h-5 w-5 text-orange-600" />
                                @else
                                    <x-heroicon-o-check-circle class="h-5 w-5 text-green-600" />
                                @endif
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Account Standing</h4>
                        </div>
                        <div class="rounded-xl {{ $selectedBalanceTotal > 0 ? 'border-orange-200 bg-orange-50 text-orange-800' : 'border-green-200 bg-green-50 text-green-800' }} border px-4 py-4">
                            <p class="text-sm font-semibold">
                                {{ $selectedBalanceTotal > 0 ? 'Buyer has unpaid balance' : 'No unpaid balance' }}
                            </p>
                            <p class="mt-2 text-sm">
                                {{ $selectedBalanceTotal > 0 ? 'Review the transactions below and add payment to the buyer balance.' : 'All listed transactions are fully settled.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex items-center">
                            <div class="mr-3 rounded-lg bg-indigo-100 p-2">
                                <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-indigo-600" />
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Past Transactions</h4>
                            <span class="ml-2 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                {{ $selectedBuyerSales->count() }} records
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3 overflow-y-auto p-4" style="max-height: 52vh;">
                        @forelse($selectedBuyerSales as $sale)
                            @php
                                $paymentProgress = $sale->total_amount > 0
                                    ? min(100, (($sale->paid_amount / (float) $sale->total_amount) * 100))
                                    : 0;
                            @endphp
                            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50/30">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-base font-semibold text-gray-900">Sale #{{ $sale->id }}</span>
                                    <x-status-badge :status="\App\Constants\SalesStatusConstant::getDisplayName($sale->status)" size="sm" />
                                    <span class="text-sm text-gray-500">{{ $sale->sales_date?->format('M d, Y') }}</span>
                                </div>
                                <p class="mt-2 truncate text-sm text-gray-600" title="{{ $sale->formatted_items }}">{{ $sale->formatted_items }}</p>

                                @php($lineItems = $buyerLedgerLineItems($sale))
                                @if($lineItems->isNotEmpty())
                                    <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                        <table class="w-full min-w-[58rem] text-left text-xs">
                                            <thead class="bg-gray-50 text-gray-500">
                                                <tr>
                                                    <th class="px-3 py-2 font-medium">Item</th>
                                                    <th class="px-3 py-2 text-right font-medium">Qty</th>
                                                    <th class="px-3 py-2 font-medium">Box No.</th>
                                                    <th class="px-3 py-2 text-right font-medium">Total</th>
                                                    <th class="px-3 py-2 text-right font-medium">Paid</th>
                                                    <th class="px-3 py-2 text-right font-medium">Balance</th>
                                                    <th class="px-3 py-2 text-center font-medium">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($lineItems as $lineItemIndex => $lineItem)
                                                    <tr>
                                                        <td class="px-3 py-2 text-gray-700">{{ $lineItem['item'] }}</td>
                                                        <td class="px-3 py-2 text-right tabular-nums text-gray-900">{{ number_format($lineItem['quantity']) }}</td>
                                                        <td class="px-3 py-2 text-gray-600">
                                                            {{ $lineItem['fish_boxes']->isNotEmpty() ? $lineItem['fish_boxes']->implode(', ') : 'No box recorded' }}
                                                        </td>
                                                        @if($lineItemIndex === 0)
                                                            <td rowspan="{{ $lineItems->count() }}" class="px-3 py-2 text-right align-middle text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) $sale->total_amount, 2) }}</td>
                                                            <td rowspan="{{ $lineItems->count() }}" class="px-3 py-2 text-right align-middle text-sm font-semibold tabular-nums text-green-700">₱{{ number_format((float) $sale->paid_amount, 2) }}</td>
                                                            <td rowspan="{{ $lineItems->count() }}" class="px-3 py-2 text-right align-middle text-sm font-semibold tabular-nums {{ $sale->remaining_amount > 0 ? 'text-orange-600' : 'text-green-700' }}">₱{{ number_format((float) $sale->remaining_amount, 2) }}</td>
                                                            <td rowspan="{{ $lineItems->count() }}" class="px-3 py-2 text-center align-middle">
                                                                <span class="inline-flex rounded-xl {{ $sale->remaining_amount > 0 ? 'border-orange-200 bg-orange-50 text-orange-700' : 'border-green-200 bg-green-50 text-green-700' }} border px-3 py-2 text-sm font-semibold">
                                                                    {{ $sale->remaining_amount > 0 ? 'Has balance' : 'Settled' }}
                                                                </span>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <div class="mt-3 h-2 w-full max-w-md rounded-full bg-gray-200">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-green-500 to-green-600" style="width: {{ $paymentProgress }}%"></div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                                No transactions for this buyer yet.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </x-app-modal>
    @endif

    @if(request('modal') === 'create')
        <x-app-modal
            title="Add Buyer"
            subtitle="Create a buyer profile before any transaction is made."
            :close-url="route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page')]))"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
                    <x-heroicon-o-user-plus class="h-5 w-5" />
                </div>
            </x-slot:icon>

            @if($brokerViewReadOnly)
                <div class="space-y-5">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Buyer details are read-only in this broker workspace.
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-5">
                        <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page')])) }}"
                           class="inline-flex rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                            Back
                        </a>
                    </div>
                </div>
            @else
                <form action="{{ route('broker.buyers.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="buyer_search" value="{{ $search }}">
                    <input type="hidden" name="buyer_page" value="{{ request('page') }}">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="buyer_create_first_name" class="mb-2 block text-sm font-medium text-gray-700">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="buyer_create_first_name"
                                   name="first_name"
                                   required
                                   value="{{ old('first_name') }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_create_middle_name" class="mb-2 block text-sm font-medium text-gray-700">Middle Name</label>
                            <input type="text"
                                   id="buyer_create_middle_name"
                                   name="middle_name"
                                   value="{{ old('middle_name') }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('middle_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_create_last_name" class="mb-2 block text-sm font-medium text-gray-700">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="buyer_create_last_name"
                                   name="last_name"
                                   required
                                   value="{{ old('last_name') }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_create_contact" class="mb-2 block text-sm font-medium text-gray-700">Contact Number</label>
                            <input type="text"
                                   id="buyer_create_contact"
                                   name="contact"
                                   value="{{ old('contact') }}"
                                   placeholder="09XXXXXXXXX"
                                   inputmode="numeric"
                                   maxlength="11"
                                   pattern="09[0-9]{9}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('contact')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page')])) }}"
                           class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 sm:w-auto">
                            Add Buyer
                        </button>
                    </div>
                </form>
            @endif
        </x-app-modal>
    @endif

    @if(request('modal') === 'edit' && $selectedBuyer)
        <x-app-modal
            title="Edit Buyer Details"
            :subtitle="$selectedBuyer->name . ' · ' . ($selectedBuyer->contact ?: 'No contact number')"
            :close-url="route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => request('buyer')]))"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-sm">
                    <x-heroicon-o-pencil-square class="h-5 w-5" />
                </div>
            </x-slot:icon>

            @if($brokerViewReadOnly)
                <div class="space-y-5">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Buyer details are read-only in this broker workspace.
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-5">
                        <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => request('buyer')])) }}"
                           class="inline-flex rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                            Back
                        </a>
                    </div>
                </div>
            @else
                <form action="{{ route('broker.buyers.update', $selectedBuyer->id) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="buyer_search" value="{{ $search }}">
                    <input type="hidden" name="buyer_page" value="{{ request('page') }}">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="buyer_edit_first_name" class="mb-2 block text-sm font-medium text-gray-700">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="buyer_edit_first_name"
                                   name="first_name"
                                   required
                                   value="{{ old('first_name', $selectedBuyer->first_name) }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_edit_middle_name" class="mb-2 block text-sm font-medium text-gray-700">Middle Name</label>
                            <input type="text"
                                   id="buyer_edit_middle_name"
                                   name="middle_name"
                                   value="{{ old('middle_name', $selectedBuyer->middle_name) }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('middle_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_edit_last_name" class="mb-2 block text-sm font-medium text-gray-700">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="buyer_edit_last_name"
                                   name="last_name"
                                   required
                                   value="{{ old('last_name', $selectedBuyer->last_name) }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_edit_contact" class="mb-2 block text-sm font-medium text-gray-700">Contact Number</label>
                            <input type="text"
                                   id="buyer_edit_contact"
                                   name="contact"
                                   value="{{ old('contact', $selectedBuyer->contact) }}"
                                   placeholder="09XXXXXXXXX"
                                   inputmode="numeric"
                                   maxlength="11"
                                   pattern="09[0-9]{9}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('contact')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => request('buyer')])) }}"
                           class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 sm:w-auto">
                            Save Changes
                        </button>
                    </div>
                </form>
            @endif
        </x-app-modal>
    @endif

    @if(request('modal') === 'payment')
        <x-app-modal
            title="Add Buyer Payment"
            :subtitle="$selectedBuyer ? $selectedBuyer->name . ' · Balance ₱' . number_format($selectedBalanceTotal, 2) : 'The selected buyer is not available.'"
            :close-url="route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => request('buyer')]))"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-sm">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
            </x-slot:icon>

            @if($brokerViewReadOnly)
                <div class="space-y-5">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Payment actions are read-only in this broker workspace.
                    </div>
                    <div class="flex justify-end border-t border-gray-100 pt-5">
                        <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => request('buyer')])) }}"
                           class="inline-flex rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                            Back
                        </a>
                    </div>
                </div>
            @elseif($selectedBuyer && $selectedBalanceTotal > 0)
                <div class="space-y-6">
                    <div class="rounded-xl bg-gray-50 p-4">
                        <h4 class="mb-3 text-sm font-medium text-gray-700">Payment Summary</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Buyer</span>
                                <span class="text-right text-sm font-bold text-gray-900">{{ $selectedBuyer->name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Unpaid Transactions</span>
                                <span class="text-right text-sm font-bold tabular-nums text-gray-900">{{ number_format($selectedBuyerSales->filter(fn ($sale) => (float) $sale->remaining_amount > 0)->count()) }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-200 pt-2">
                                <span class="text-sm text-gray-600">Buyer Balance</span>
                                <span class="text-right text-sm font-bold tabular-nums text-orange-600">₱{{ number_format($selectedBalanceTotal, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('broker.buyers.payments.store') }}"
                          method="POST"
                          class="space-y-5"
                          x-data="{ paymentMethod: @js(old('payment_method', '')), requiresReferenceNumber() { return ['GCash', 'Bank Transfer'].includes(this.paymentMethod); } }">
                        @csrf
                        <input type="hidden" name="buyer_id" value="{{ $selectedBuyer->id }}">
                        <input type="hidden" name="buyer_search" value="{{ $search }}">
                        <input type="hidden" name="buyer_page" value="{{ request('page') }}">

                        <div>
                            <label for="buyer_ledger_paid_amount" class="mb-2 block text-sm font-medium text-gray-700">
                                Paid Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="currency-input-group">
                                <span class="currency-input-symbol">₱</span>
                                <input type="number"
                                       id="buyer_ledger_paid_amount"
                                       name="paid_amount"
                                       min="0.01"
                                       max="{{ $selectedBalanceTotal }}"
                                       step="0.01"
                                       required
                                       value="{{ old('paid_amount') }}"
                                       class="currency-input-field"
                                       placeholder="0.00">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Maximum payment: ₱{{ number_format($selectedBalanceTotal, 2) }}
                            </p>
                            @error('paid_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_ledger_payment_date" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="buyer_ledger_payment_date"
                                   name="payment_date"
                                   required
                                   value="{{ old('payment_date', date('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('payment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="buyer_ledger_payment_method" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select id="buyer_ledger_payment_method"
                                    name="payment_method"
                                    required
                                    x-model="paymentMethod"
                                    class="app-select w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Payment Method</option>
                                @foreach(['Cash', 'GCash', 'Bank Transfer', 'Check', 'Other'] as $paymentMethod)
                                    <option value="{{ $paymentMethod }}" {{ old('payment_method') === $paymentMethod ? 'selected' : '' }}>{{ $paymentMethod }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="requiresReferenceNumber()" x-cloak>
                            <label for="buyer_ledger_reference_number" class="mb-2 block text-sm font-medium text-gray-700">
                                Reference Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="buyer_ledger_reference_number"
                                   name="reference_number"
                                   value="{{ old('reference_number') }}"
                                   maxlength="100"
                                   :required="requiresReferenceNumber()"
                                   placeholder="Enter GCash or bank reference number"
                                   class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                            <a href="{{ route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page'), 'buyer' => request('buyer')])) }}"
                               class="inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex w-full justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto">
                                Add Payment
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This buyer has no unpaid balance available for payment.
                </div>
            @endif
        </x-app-modal>
    @endif

    @if(request('modal') === 'print' && $selectedBuyer)
        <div
            id="buyer-ledger-statement-print"
            data-watermark-logo-url="{{ asset('image/logo.png') }}"
            hidden
        >
            <div style="max-width: 760px; margin: 0 auto; color: #111827;">
                <div style="text-align: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 18px; margin-bottom: 18px;">
                    <h1 style="margin: 0 0 6px; font-size: 24px; line-height: 1.2;">Buyer Ledger Statement</h1>
                    <p style="margin: 4px 0; font-size: 13px; color: #4b5563;">{{ $broker?->name ?? 'Broker' }}{{ $broker?->stall_name ? ' · ' . $broker->stall_name : '' }}</p>
                    <p style="margin: 4px 0; font-size: 12px; color: #6b7280;">Printed {{ now()->format('M d, Y h:i A') }}</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px;">
                        <p style="margin: 0 0 5px; font-size: 12px; color: #6b7280;">Buyer</p>
                        <p style="margin: 0; font-size: 16px; font-weight: 700;">{{ $selectedBuyer->name }}</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">{{ $selectedBuyer->contact ?: 'No contact number' }}</p>
                    </div>
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px;">
                        <p style="margin: 0 0 5px; font-size: 12px; color: #6b7280;">Account Standing</p>
                        <p style="margin: 0; font-size: 16px; font-weight: 700; color: {{ $selectedBalanceTotal > 0 ? '#ea580c' : '#059669' }};">
                            {{ $selectedBalanceTotal > 0 ? 'Has Balance' : 'Settled' }}
                        </p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">{{ number_format($selectedBuyerSales->count()) }} transaction(s)</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px;">
                        <p style="margin: 0 0 8px; font-size: 12px; color: #6b7280;">Total Sales</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; text-align: right;">₱{{ number_format($selectedSalesTotal, 2) }}</p>
                    </div>
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px;">
                        <p style="margin: 0 0 8px; font-size: 12px; color: #6b7280;">Paid</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #059669; text-align: right;">₱{{ number_format($selectedPaidTotal, 2) }}</p>
                    </div>
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px;">
                        <p style="margin: 0 0 8px; font-size: 12px; color: #6b7280;">Balance</p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: {{ $selectedBalanceTotal > 0 ? '#ea580c' : '#059669' }}; text-align: right;">₱{{ number_format($selectedBalanceTotal, 2) }}</p>
                    </div>
                </div>

                <h2 style="margin: 0 0 10px; font-size: 15px;">Past Transactions</h2>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Date</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Transaction</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">Sale Total</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">Paid</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">Balance</th>
                            <th style="border: 1px solid #e5e7eb; padding: 8px; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($selectedBuyerSales as $sale)
                            @php($lineItems = $buyerLedgerLineItems($sale))
                            <tr>
                                <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ $sale->sales_date?->format('M d, Y') }}</td>
                                <td style="border: 1px solid #e5e7eb; padding: 8px;">
                                    <strong>Sale #{{ $sale->id }}</strong><br>
                                    @if($lineItems->isNotEmpty())
                                        <table style="width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 11px;">
                                            <thead>
                                                <tr style="background: #f9fafb;">
                                                    <th style="border: 1px solid #e5e7eb; padding: 5px; text-align: left;">Item</th>
                                                    <th style="border: 1px solid #e5e7eb; padding: 5px; text-align: right;">Qty</th>
                                                    <th style="border: 1px solid #e5e7eb; padding: 5px; text-align: left;">Box No.</th>
                                                    <th style="border: 1px solid #e5e7eb; padding: 5px; text-align: right;">Unit Price</th>
                                                    <th style="border: 1px solid #e5e7eb; padding: 5px; text-align: right;">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lineItems as $lineItem)
                                                    <tr>
                                                        <td style="border: 1px solid #e5e7eb; padding: 5px;">{{ $lineItem['item'] }}</td>
                                                        <td style="border: 1px solid #e5e7eb; padding: 5px; text-align: right;">{{ number_format($lineItem['quantity']) }}</td>
                                                        <td style="border: 1px solid #e5e7eb; padding: 5px;">{{ $lineItem['fish_boxes']->isNotEmpty() ? $lineItem['fish_boxes']->implode(', ') : 'No box recorded' }}</td>
                                                        <td style="border: 1px solid #e5e7eb; padding: 5px; text-align: right;">₱{{ number_format($lineItem['unit_price'], 2) }}</td>
                                                        <td style="border: 1px solid #e5e7eb; padding: 5px; text-align: right;">₱{{ number_format($lineItem['sub_total'], 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <span style="color: #6b7280;">{{ $sale->formatted_items ?: 'No items recorded' }}</span>
                                    @endif
                                </td>
                                <td style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">₱{{ number_format((float) $sale->total_amount, 2) }}</td>
                                <td style="border: 1px solid #e5e7eb; padding: 8px; text-align: right;">₱{{ number_format((float) $sale->paid_amount, 2) }}</td>
                                <td style="border: 1px solid #e5e7eb; padding: 8px; text-align: right; font-weight: 700;">₱{{ number_format((float) $sale->remaining_amount, 2) }}</td>
                                <td style="border: 1px solid #e5e7eb; padding: 8px;">{{ \App\Constants\SalesStatusConstant::getDisplayName($sale->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="border: 1px solid #e5e7eb; padding: 14px; text-align: center; color: #6b7280;">No transactions for this buyer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <script src="{{ $printReceiptScriptUrl }}"></script>
        <script>
            window.addEventListener('load', function () {
                window.printReceipt('buyer-ledger-statement-print', @json('Buyer Ledger - ' . $selectedBuyer->name));

                const cleanUrl = @json(route('broker.buyers.index', array_filter(['search' => $search, 'page' => request('page')])));
                window.setTimeout(function () {
                    window.history.replaceState({}, '', cleanUrl);
                }, 600);
            });
        </script>
    @endif
</div>
@endsection
