<?php
require 'c:/xampp/htdocs/maramag_fishlanding/POS/vendor/autoload.php';
$app = require 'c:/xampp/htdocs/maramag_fishlanding/POS/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$sale = App\Models\Sales::with(['buyer','salesDetails.fishBoxPurchase.fishBox','salesDetails.fishBoxPurchase.fishType','salesPayments'])->first();
if (!$sale) {
    echo "NO_SALES\n";
    exit;
}
echo json_encode([
  'sale_id' => $sale->id,
  'broker_id' => $sale->broker_id,
  'buyer_name' => $sale->buyer_name,
  'buyer_contact' => $sale->buyer_contact,
  'details_count' => $sale->salesDetails->count(),
  'first_detail' => $sale->salesDetails->first() ? [
      'box_id' => $sale->salesDetails->first()->box_id,
      'item' => $sale->salesDetails->first()->item,
      'quantity' => $sale->salesDetails->first()->quantity,
      'fish_box_name' => $sale->salesDetails->first()->fishBox?->name,
      'fish_type_name' => $sale->salesDetails->first()->fishBoxPurchase?->fishType?->name,
  ] : null,
  'payments_count' => $sale->salesPayments->count(),
], JSON_PRETTY_PRINT);
