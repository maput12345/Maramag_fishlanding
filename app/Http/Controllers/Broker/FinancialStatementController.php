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
        $statement = FinancialStatementEntry::getDailyStatement($brokerId, $statementDate);
        $salesBreakdown = FinancialStatementEntry::getDailySalesBreakdown($brokerId, $statementDate);
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

        FinancialStatementEntry::create([
            'broker_id' => $brokerId,
            'created_by_user_id' => Auth::id(),
            'statement_date' => $validated['statement_date'],
            'entry_type' => $validated['entry_type'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
        ]);

        return redirect()
            ->route('broker.financial-statements.index', [
                'statement_date' => $validated['statement_date'],
            ])
            ->with('success', 'Daily financial statement entry recorded successfully.');
    }

    /**
     * Remove a manual daily financial statement adjustment.
     */
    public function destroy(Request $request, FinancialStatementEntry $entry): RedirectResponse
    {
        $brokerId = $this->resolveCurrentBrokerId();
        abort_unless($entry->broker_id === $brokerId, 403);

        $statementDate = $request->query('statement_date')
            ?: optional($entry->statement_date)->toDateString()
            ?: now()->toDateString();

        $entry->delete();

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
