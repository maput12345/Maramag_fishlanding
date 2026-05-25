<?php

namespace App\Http\Controllers\Broker;

use App\Constants\SalesStatusConstant;
use App\Http\Controllers\Controller;
use App\Models\Broker;
use App\Models\Buyer;
use App\Models\PaymentRecord;
use App\Models\SalesTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuyerLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $broker = $brokerId ? Broker::find($brokerId) : null;
        $search = trim((string) $request->get('search'));

        $paymentTotals = DB::table('PaymentRecord')
            ->select('sale_id', DB::raw('SUM(paid_amount) as paid_total'))
            ->groupBy('sale_id');

        $buyers = Buyer::query()
            ->where('Buyer.broker_id', $brokerId)
            ->leftJoin('SalesTransaction', function ($join) {
                $join->on('Buyer.id', '=', 'SalesTransaction.buyer_id')
                    ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());
            })
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join) {
                $join->on('SalesTransaction.id', '=', 'payment_totals.sale_id');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('Buyer.first_name', 'like', "%{$search}%")
                        ->orWhere('Buyer.middle_name', 'like', "%{$search}%")
                        ->orWhere('Buyer.last_name', 'like', "%{$search}%")
                        ->orWhere('Buyer.contact', 'like', "%{$search}%")
                        ->orWhereRaw("TRIM(CONCAT_WS(' ', Buyer.first_name, Buyer.middle_name, Buyer.last_name)) like ?", ["%{$search}%"]);
                });
            })
            ->select([
                'Buyer.id',
                'Buyer.broker_id',
                'Buyer.first_name',
                'Buyer.middle_name',
                'Buyer.last_name',
                'Buyer.contact',
            ])
            ->selectRaw('
                COUNT(SalesTransaction.id) as transactions_count,
                COALESCE(SUM(SalesTransaction.total_amount), 0) as total_sales,
                COALESCE(SUM(COALESCE(payment_totals.paid_total, 0)), 0) as total_paid,
                COALESCE(SUM(CASE
                    WHEN SalesTransaction.total_amount > COALESCE(payment_totals.paid_total, 0)
                    THEN SalesTransaction.total_amount - COALESCE(payment_totals.paid_total, 0)
                    ELSE 0
                END), 0) as balance,
                MAX(SalesTransaction.sales_date) as last_purchase_date
            ')
            ->groupBy('Buyer.id', 'Buyer.broker_id', 'Buyer.first_name', 'Buyer.middle_name', 'Buyer.last_name', 'Buyer.contact')
            ->orderByDesc('balance')
            ->orderByDesc('last_purchase_date')
            ->paginate(12);

        $buyersOnPage = $buyers->getCollection();
        $buyerActionSales = SalesTransaction::query()
            ->withPaidAmount()
            ->where('broker_id', $brokerId)
            ->whereIn('buyer_id', $buyersOnPage->pluck('id')->all())
            ->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
            ->orderByDesc('sales_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('buyer_id');

        $buyers->setCollection($buyersOnPage->map(function (Buyer $buyer) use ($buyerActionSales) {
            $sales = $buyerActionSales->get($buyer->id, collect());
            $latestSale = $sales->first();

            $buyer->setAttribute('action_latest_sale_id', $latestSale?->id);

            return $buyer;
        }));

        $selectedBuyer = null;
        $selectedBuyerSales = collect();

        if ($request->filled('buyer')) {
            $selectedBuyer = Buyer::query()
                ->forBroker($brokerId)
                ->find($request->integer('buyer'));

            if ($selectedBuyer) {
                $selectedBuyerSales = SalesTransaction::query()
                    ->withPaidAmount()
                    ->with([
                        'salesDetails.fishBoxPurchase.fishType',
                        'salesDetails.fishBoxPurchase.fishBox' => function ($fishBoxQuery) {
                            $fishBoxQuery->withBrokerBoxNumber();
                        },
                        'salesPayments',
                    ])
                    ->where('broker_id', $brokerId)
                    ->where('buyer_id', $selectedBuyer->id)
                    ->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
                    ->orderByDesc('sales_date')
                    ->orderByDesc('created_at')
                    ->get();

                $selectedBuyerSales->each(function (SalesTransaction $sale) {
                    $sale->formatted_items = $sale->getFormattedItems();
                });
            }
        }

        $buyerBalanceRows = Buyer::query()
            ->where('Buyer.broker_id', $brokerId)
            ->leftJoin('SalesTransaction', function ($join) {
                $join->on('Buyer.id', '=', 'SalesTransaction.buyer_id')
                    ->whereIn('SalesTransaction.status', SalesStatusConstant::getAllActiveStatuses());
            })
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join) {
                $join->on('SalesTransaction.id', '=', 'payment_totals.sale_id');
            })
            ->select('Buyer.id')
            ->selectRaw('COALESCE(SUM(CASE
                WHEN SalesTransaction.total_amount > COALESCE(payment_totals.paid_total, 0)
                THEN SalesTransaction.total_amount - COALESCE(payment_totals.paid_total, 0)
                ELSE 0
            END), 0) as balance')
            ->groupBy('Buyer.id')
            ->get();

        $totalBuyers = $buyerBalanceRows->count();
        $buyersWithBalance = $buyerBalanceRows->filter(fn ($buyer) => (float) $buyer->balance > 0)->count();

        return view('broker.buyers.index', compact(
            'buyers',
            'search',
            'selectedBuyer',
            'selectedBuyerSales',
            'broker',
            'totalBuyers',
            'buyersWithBalance'
        ));
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $user = Auth::user();

        $validated = $request->validate([
            'buyer_id' => ['required', 'integer'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:255'],
            'buyer_search' => ['nullable', 'string', 'max:255'],
            'buyer_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $returnParameters = array_filter([
            'search' => $validated['buyer_search'] ?? null,
            'page' => $validated['buyer_page'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        $paymentModalParameters = array_merge($returnParameters, [
            'buyer' => $validated['buyer_id'],
            'modal' => 'payment',
        ]);

        if ($user?->isAdmin() && Broker::isAdminBrokerViewReadOnly($user)) {
            return redirect()->route('broker.buyers.index', $returnParameters)
                ->with('error', 'Payment actions are read-only in this broker workspace.');
        }

        $buyer = Buyer::query()
            ->forBroker($brokerId)
            ->find($validated['buyer_id']);

        if (!$buyer) {
            return redirect()->route('broker.buyers.index', $returnParameters)
                ->with('error', 'The selected buyer does not belong to your broker account.');
        }

        $paymentAmount = round((float) $validated['paid_amount'], 2);
        $paymentCount = 0;

        $totalBalance = SalesTransaction::query()
            ->withPaidAmount()
            ->where('broker_id', $brokerId)
            ->where('buyer_id', $buyer->id)
            ->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
            ->get()
            ->sum(fn (SalesTransaction $sale) => (float) $sale->remaining_amount);

        if ($paymentAmount > round((float) $totalBalance, 2)) {
            return redirect()->route('broker.buyers.index', $paymentModalParameters)
                ->withErrors(['paid_amount' => 'Payment cannot exceed the buyer balance of ₱' . number_format((float) $totalBalance, 2) . '.'])
                ->withInput();
        }

        DB::transaction(function () use ($brokerId, $buyer, $validated, $paymentAmount, &$paymentCount) {
            $remainingPayment = $paymentAmount;

            $sales = SalesTransaction::query()
                ->withPaidAmount()
                ->where('broker_id', $brokerId)
                ->where('buyer_id', $buyer->id)
                ->whereIn('status', SalesStatusConstant::getAllActiveStatuses())
                ->orderBy('sales_date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->filter(fn (SalesTransaction $sale) => (float) $sale->remaining_amount > 0);

            foreach ($sales as $sale) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $appliedAmount = min(round((float) $sale->remaining_amount, 2), round($remainingPayment, 2));

                PaymentRecord::create([
                    'sale_id' => $sale->id,
                    'paid_amount' => $appliedAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                ]);

                $sale->updatePaidAmount();
                $sale->updatePaymentStatus();

                $paymentCount++;
                $remainingPayment = round($remainingPayment - $appliedAmount, 2);
            }
        });

        return redirect()->route('broker.buyers.index', $returnParameters)
            ->with('success', 'Payment applied to ' . $paymentCount . ' buyer transaction' . ($paymentCount === 1 ? '' : 's') . '.');
    }
}
