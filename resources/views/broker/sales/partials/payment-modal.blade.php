{{-- Add Payment Modal --}}
@if(request('modal') === 'payment')
    @if($saleForPayment)
        <x-app-modal
            title="Add Payment"
            subtitle="Record a payment and update the sale balance in one clean step."
            :close-url="$salesBaseUrl"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-sm">
                    <x-heroicon-o-currency-dollar class="h-5 w-5" />
                </div>
            </x-slot:icon>

                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Payment Summary</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Total Bill:</span>
                                    <span class="text-sm font-bold text-gray-900">₱{{ number_format($saleForPayment->total_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">To Pay Total:</span>
                                    <span class="text-sm font-bold text-green-600">₱{{ number_format($saleForPayment->paid_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t pt-2">
                                    <span class="text-sm text-gray-600">Running Balance:</span>
                                    <span class="text-sm font-bold text-orange-600">₱{{ number_format($saleForPayment->remaining_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('broker.sales-payments.store') }}" method="POST" class="space-y-6" x-data="paymentForm()" x-init="initializePaymentForm()" data-sales-async-form>
                            @csrf

                            <input type="hidden" name="sales_id" value="{{ request('sale') }}">

                            <div>
                                <label for="paid_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Paid Amount <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="paid_amount" name="paid_amount" required
                                       step="0.01" min="0.01" :max="maxPaymentAmount"
                                       x-model="paidAmount"
                                       @input="validatePaymentAmount()"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       placeholder="0.00">
                                <div class="mt-1 text-xs text-gray-500">
                                    Maximum payment: ₱<span x-text="maxPaymentAmount.toFixed(2)"></span>
                                </div>
                                <div x-show="paymentError" class="mt-1 text-sm text-red-600" x-text="paymentError"></div>
                                @error('paid_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="payment_date" name="payment_date" required
                                       value="{{ old('payment_date', date('Y-m-d')) }}"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('payment_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Method <span class="text-red-500">*</span>
                                </label>
                                <select id="payment_method" name="payment_method" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Select Payment Method</option>
                                    <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="GCash" {{ old('payment_method') == 'GCash' ? 'selected' : '' }}>GCash</option>
                                    <option value="Bank Transfer" {{ old('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="Check" {{ old('payment_method') == 'Check' ? 'selected' : '' }}>Check</option>
                                    <option value="Other" {{ old('payment_method') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('payment_method')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Modal Footer --}}
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                                <button
                                   type="button"
                                   data-sales-modal-close
                                   data-close-url="{{ $salesBaseUrl }}"
                                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="paymentError || paidAmount <= 0"
                                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                                    Add Payment
                                </button>
                            </div>
                        </form>
        </x-app-modal>
    @else
        {{-- Sale not found for payment --}}
        <x-app-modal
            title="Sale Not Found"
            subtitle="The selected sale is no longer available for payment."
            :close-url="$salesBaseUrl"
        >
            <x-slot:icon>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
            </x-slot:icon>

            <div class="py-4 text-center">
                <p class="text-sm text-gray-500 mb-6">The sale you're trying to add payment for could not be found or you don't have permission to access it.</p>
                <button
                   type="button"
                   data-sales-modal-close
                   data-close-url="{{ $salesBaseUrl }}"
                   class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
                    Back to Sales
                </button>
            </div>
        </x-app-modal>
    @endif
@endif

