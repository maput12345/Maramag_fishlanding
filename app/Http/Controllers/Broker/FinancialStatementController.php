<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialStatementEntryRequest;
use App\Models\Broker;
use App\Models\FinancialStatementEntry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialStatementController extends Controller
{
    /**
     * Show the broker's daily financial statement.
     */
    public function index(Request $request): View
    {
        $brokerId = $this->resolveCurrentBrokerId();
        $statementDate = $this->resolveStatementDate($request->get('statement_date'));
        $entries = FinancialStatementEntry::query()
            ->forBroker($brokerId)
            ->forStatementDate($statementDate)
            ->orderBy('created_at', 'desc')
            ->get();
        $entryTypeOptions = FinancialStatementEntry::typeOptions();
        $expenseCategoryOptions = FinancialStatementEntry::expenseCategoryOptions();
        $statement = FinancialStatementEntry::getDailyStatement($brokerId, $statementDate);
        $salesBreakdown = FinancialStatementEntry::getDailySalesBreakdown($brokerId, $statementDate);
        $expenseAnalytics = $this->getMajorExpenseAnalytics($brokerId, $statementDate);
        $entryGroups = collect($entryTypeOptions)
            ->map(function (string $label, string $type) use ($entries): array {
                $groupEntries = $entries
                    ->where('entry_type', $type)
                    ->values();

                return [
                    'type' => $type,
                    'label' => $label,
                    'entries' => $groupEntries,
                    'entries_count' => $groupEntries->count(),
                    'total' => (float) $groupEntries->sum('amount'),
                ];
            })
            ->values();

        return view('broker.financial-statements.index', [
            'statementDate' => $statementDate->toDateString(),
            'statement' => $statement,
            'salesBreakdown' => $salesBreakdown,
            'entryTypeOptions' => $entryTypeOptions,
            'expenseCategoryOptions' => $expenseCategoryOptions,
            'expenseAnalytics' => $expenseAnalytics,
            'entryGroups' => $entryGroups,
        ]);
    }

    /**
     * Store a manual daily financial statement adjustment.
     */
    public function store(FinancialStatementEntryRequest $request): RedirectResponse
    {
        $brokerId = $this->resolveCurrentBrokerId();
        $validated = $request->validated();
        $userId = Auth::id();

        DB::transaction(function () use ($brokerId, $userId, $validated): void {
            if ($validated['entry_form_mode'] === 'expenses') {
                foreach ((array) ($validated['expenses'] ?? []) as $expense) {
                    if (!isset($expense['amount']) || $expense['amount'] === null || $expense['amount'] === '') {
                        continue;
                    }

                    FinancialStatementEntry::create([
                        'broker_id' => $brokerId,
                        'created_by_user_id' => $userId,
                        'statement_date' => $validated['statement_date'],
                        'entry_type' => FinancialStatementEntry::TYPE_SGA,
                        'description' => $this->resolveExpenseDescription($expense),
                        'amount' => $expense['amount'],
                    ]);
                }

                return;
            }

            if (isset($validated['loss_amount']) && $validated['loss_amount'] !== null && $validated['loss_amount'] !== '') {
                FinancialStatementEntry::create([
                    'broker_id' => $brokerId,
                    'created_by_user_id' => $userId,
                    'statement_date' => $validated['statement_date'],
                    'entry_type' => FinancialStatementEntry::TYPE_LOSS_ON_SALE,
                    'description' => trim((string) $validated['loss_description']),
                    'amount' => $validated['loss_amount'],
                ]);
            }
        });

        return redirect()
            ->route('broker.financial-statements.index', [
                'statement_date' => $validated['statement_date'],
            ])
            ->with('success', 'Daily financial statement entry recorded successfully.');
    }

    /**
     * Update a manual daily financial statement adjustment.
     */
    public function update(Request $request, FinancialStatementEntry $entry): RedirectResponse
    {
        $brokerId = $this->resolveCurrentBrokerId();
        abort_unless($entry->broker_id === $brokerId, 403);

        $validated = $request->validate([
            'statement_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $entry->update([
            'statement_date' => $validated['statement_date'],
            'description' => trim((string) $validated['description']),
            'amount' => $validated['amount'],
        ]);

        return redirect()
            ->route('broker.financial-statements.index', [
                'statement_date' => $validated['statement_date'],
            ])
            ->with('success', 'Daily financial statement entry updated successfully.');
    }

    /**
     * Remove a manual daily financial statement adjustment.
     */
    public function destroy(Request $request, FinancialStatementEntry $entry): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $brokerId = $this->resolveCurrentBrokerId();
        abort_unless($entry->broker_id === $brokerId, 403);

        $statementDate = $request->query('statement_date')
            ?: optional($entry->statement_date)->toDateString()
            ?: now()->toDateString();
        $entryType = $entry->entry_type;

        $entry->delete();

        if ($request->expectsJson()) {
            $entries = FinancialStatementEntry::query()
                ->forBroker($brokerId)
                ->forStatementDate($statementDate)
                ->ofType($entryType)
                ->get();

            return response()->json([
                'message' => 'Daily financial statement entry removed successfully.',
                'entry_type' => $entryType,
                'entries_count' => $entries->count(),
                'group_total' => (float) $entries->sum('amount'),
                'group_total_formatted' => '₱' . number_format((float) $entries->sum('amount'), 2),
            ]);
        }

        return redirect()
            ->route('broker.financial-statements.index', [
                'statement_date' => $statementDate,
            ])
            ->with('success', 'Daily financial statement entry removed successfully.');
    }

    /**
     * Resolve the broker profile used by the current session.
     */
    private function resolveCurrentBrokerId(): int
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());

        abort_if(!$brokerId, 403, 'Only broker accounts with an active broker profile can access the financial statement.');

        return $brokerId;
    }

    /**
     * Turn the broker-friendly category row into the stored entry description.
     *
     * @param array<string, mixed> $expense
     */
    private function resolveExpenseDescription(array $expense): string
    {
        $category = $expense['category'] ?? '';

        if ($category !== FinancialStatementEntry::EXPENSE_CATEGORY_OTHER) {
            return $category;
        }

        return trim((string) ($expense['description'] ?? ''));
    }

    /**
     * Build the line chart data for the major daily expense categories.
     *
     * @return array<string, mixed>
     */
    private function getMajorExpenseAnalytics(int $brokerId, Carbon $statementDate): array
    {
        $categories = [
            'Gas' => [
                'label' => 'Gas',
                'color' => '#2563eb',
            ],
            'Ice/Cellophane' => [
                'label' => 'Ice/Cellophane',
                'color' => '#0891b2',
            ],
        ];

        $startDate = $statementDate->copy()->subDays(6)->startOfDay();
        $endDate = $statementDate->copy()->endOfDay();
        $dates = collect(range(0, 6))
            ->map(fn (int $offset): Carbon => $startDate->copy()->addDays($offset));

        $totals = FinancialStatementEntry::query()
            ->forBroker($brokerId)
            ->where('entry_type', FinancialStatementEntry::TYPE_SGA)
            ->whereBetween('statement_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('description', array_keys($categories))
            ->selectRaw('statement_date, description, COALESCE(SUM(amount), 0) as total')
            ->groupBy('statement_date', 'description')
            ->get()
            ->groupBy(fn (FinancialStatementEntry $entry): string => optional($entry->statement_date)->toDateString() ?? (string) $entry->statement_date)
            ->map(fn ($entries) => $entries->pluck('total', 'description'));

        $todayKey = $statementDate->toDateString();
        $previousKey = $statementDate->copy()->subDay()->toDateString();
        $maxTotal = 0.0;

        $series = collect($categories)
            ->map(function (array $meta, string $category) use ($dates, $totals, $todayKey, $previousKey, &$maxTotal): array {
                $values = $dates->map(function (Carbon $date) use ($totals, $category, &$maxTotal): array {
                    $dateKey = $date->toDateString();
                    $amount = (float) ($totals->get($dateKey)[$category] ?? 0);
                    $maxTotal = max($maxTotal, $amount);

                    return [
                        'date' => $dateKey,
                        'label' => $date->format('M d'),
                        'amount' => $amount,
                    ];
                })->values();

                $today = (float) ($totals->get($todayKey)[$category] ?? 0);
                $previous = (float) ($totals->get($previousKey)[$category] ?? 0);
                $change = $today - $previous;

                return [
                    'key' => $category,
                    'label' => $meta['label'],
                    'color' => $meta['color'],
                    'values' => $values,
                    'today' => $today,
                    'previous' => $previous,
                    'change' => $change,
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
                ];
            })
            ->values();

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $statementDate->toDateString(),
            'period_label' => $startDate->format('M d') . ' - ' . $statementDate->format('M d, Y'),
            'labels' => $dates->map(fn (Carbon $date): string => $date->format('M d'))->values(),
            'series' => $series,
            'max_total' => max(1, $maxTotal),
        ];
    }

    /**
     * Normalize the incoming statement date for the daily workflow.
     */
    private function resolveStatementDate(?string $statementDate): Carbon
    {
        if (!$statementDate) {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse($statementDate)->startOfDay();
        } catch (\Throwable $exception) {
            return now()->startOfDay();
        }
    }
}
