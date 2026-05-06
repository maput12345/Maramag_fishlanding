<?php

namespace App\Http\Controllers\Broker;

use App\Constants\FishBoxStatusConstant;
use App\Http\Controllers\Controller;
use App\Models\Broker;
use App\Models\FishBox;
use App\Models\SalesScanSession;
use App\Models\SalesScanSessionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalesScanSessionController extends Controller
{
    public function store(): JsonResponse
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());
        $session = SalesScanSession::createForBroker($brokerId);

        return response()->json([
            'success' => true,
            'token' => $session->token,
            'scanner_url' => route('broker.sales.scan-sessions.scanner', $session->token, false),
            'poll_url' => route('broker.sales.scan-sessions.items', $session->token, false),
            'expires_at' => $session->expires_at->toIso8601String(),
        ]);
    }

    public function scanner(string $token): View
    {
        $session = $this->findBrokerSession($token);

        return view('broker.sales.remote-scanner', [
            'session' => $session,
            'isSessionActive' => $session?->isActive() ?? false,
        ]);
    }

    public function scan(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:255'],
        ]);

        $session = $this->findBrokerSession($token);

        if (!$session || !$session->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone scanner session is no longer active.',
            ], 410);
        }

        $qrCode = trim($validated['qr_code']);
        $fishBox = FishBox::getFishBoxByQrCode($qrCode, $session->broker_id);

        if (!$fishBox) {
            $this->recordScan($session, $qrCode, 'error', 'Fish box not found.');

            return response()->json([
                'success' => false,
                'message' => 'Fish box not found.',
            ], 404);
        }

        if (
            $fishBox->status !== FishBoxStatusConstant::IN_STOCK
            || !$fishBox->currentPurchase
            || !$fishBox->fish_type_id
        ) {
            $this->recordScan($session, $qrCode, 'error', 'Fish box is not available for sale.', $fishBox->id);

            return response()->json([
                'success' => false,
                'message' => 'Fish box is not available for sale.',
            ], 400);
        }

        if ($session->items()
            ->where('fish_box_id', $fishBox->id)
            ->where('status', 'accepted')
            ->whereNull('consumed_at')
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => ($fishBox->name ?? 'Fish box') . ' is already waiting on the laptop transaction screen.',
            ], 409);
        }

        $payload = $this->makeFishBoxPayload($fishBox);
        $this->recordScan($session, $qrCode, 'accepted', 'Fish box sent to transaction screen.', $fishBox->id, $payload);

        return response()->json([
            'success' => true,
            'message' => ($payload['name'] ?? 'Fish box') . ' sent to transaction screen.',
            'data' => $payload,
        ]);
    }

    public function items(string $token): JsonResponse
    {
        $session = $this->findBrokerSession($token);

        if (!$session || !$session->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone scanner session is no longer active.',
                'items' => [],
            ], 410);
        }

        $items = $session->items()
            ->whereNull('consumed_at')
            ->orderBy('id')
            ->limit(20)
            ->get();

        SalesScanSessionItem::query()
            ->whereIn('id', $items->pluck('id'))
            ->update(['consumed_at' => now()]);

        return response()->json([
            'success' => true,
            'items' => $items->map(fn (SalesScanSessionItem $item): array => [
                'id' => $item->id,
                'status' => $item->status,
                'message' => $item->message,
                'data' => $item->payload,
            ])->values(),
        ]);
    }

    public function close(string $token): JsonResponse
    {
        $session = $this->findBrokerSession($token);

        if ($session) {
            $session->update(['closed_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    private function findBrokerSession(string $token): ?SalesScanSession
    {
        $brokerId = Broker::getBrokerIdByUserId(Auth::id());

        return SalesScanSession::query()
            ->where('broker_id', $brokerId)
            ->where('token', $token)
            ->first();
    }

    private function recordScan(
        SalesScanSession $session,
        string $qrCode,
        string $status,
        string $message,
        ?int $fishBoxId = null,
        ?array $payload = null
    ): void {
        $session->items()->create([
            'fish_box_id' => $fishBoxId,
            'qr_code' => $qrCode,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
        ]);
    }

    private function makeFishBoxPayload(FishBox $fishBox): array
    {
        return [
            'id' => $fishBox->id,
            'name' => $fishBox->name,
            'qr_code' => $fishBox->qr_code,
            'fish_type_id' => $fishBox->fish_type_id,
            'fish_type' => $fishBox->fish_type_name,
            'status' => $fishBox->status,
        ];
    }
}
