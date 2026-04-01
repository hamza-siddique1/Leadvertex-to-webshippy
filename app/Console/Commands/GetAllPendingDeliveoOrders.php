<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveoController;
use App\Http\Controllers\SalesRenderController;
use App\Models\DeliveoOrder;
use App\Models\Order;
use Illuminate\Console\Command;

class GetAllPendingDeliveoOrders extends Command
{
    protected $signature = 'get-deliveo-pending-orders';

    protected $description = 'Command description';

public function handle()
{
    $deliveoController = new DeliveoController();
    $pendingOrders = $deliveoController->get_all_orders();

    $pendingOrders = collect($pendingOrders)->map(function ($order) {
        return (array) $order; // convert object to array
    })->toArray();

    // Collect all deliveo_ids from API response
    $deliveoIds = collect($pendingOrders)->pluck('deliveo_id');

    // Fetch existing orders from DB and key by deliveo_id for fast lookup
    $existingOrders = DeliveoOrder::whereIn('deliveo_id', $deliveoIds)
        ->get()
        ->keyBy('deliveo_id');

    foreach ($pendingOrders as $apiOrder) {

        $existing = $existingOrders->get($apiOrder['deliveo_id']);

        if (!$existing) {
            // New order → insert
            $order = DeliveoOrder::create([
                'deliveo_id' => $apiOrder['deliveo_id'],
                'last_modified' => $apiOrder['last_modified'] ?? null,
                'payload' => $apiOrder,
            ]);

            // Generate invoice if picked_up is set
            if ($order->picked_up && !$order->invoice_created_at) {
                GenerateInvoiceJob::dispatch($order->id);
                dump('generating invoice');
            }

        } else {


            // Existing order → check if last_modified changed
            if ($existing->last_modified != $apiOrder['last_modified']) {
                $existing->update([
                    'last_modified' => $apiOrder['last_modified'],
                    'payload' => $apiOrder,
                ]);

                // Do not generate invoice automatically
            }
        }
    }
}
}
