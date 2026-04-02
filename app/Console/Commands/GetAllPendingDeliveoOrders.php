<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveoController;
use App\Jobs\GenerateAndUploadInvoice;
use App\Jobs\GenerateDeliveoInvoice;
use App\Models\DeliveoOrder;
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
            return (array) $order;
        })->toArray();

        $deliveoIds = collect($pendingOrders)->pluck('deliveo_id');

        $existingOrders = DeliveoOrder::whereIn('deliveo_id', $deliveoIds)
            ->get()
            ->keyBy('deliveo_id');

        $hasNewInvoice = false;
        foreach ($pendingOrders as $apiOrder) {
            $existing = $existingOrders->get($apiOrder['deliveo_id']);

            if (!$existing) {

                $order = DeliveoOrder::create([
                    'deliveo_id' => $apiOrder['deliveo_id'],
                    'order_id' => $apiOrder['referenceid'],
                    'last_modified' => $apiOrder['last_modified'] ?? null,
                    'payload' => $apiOrder,
                ]);

                if (!$order->invoice_created_at) {
                    if($order->order_id) {
                        GenerateAndUploadInvoice::dispatch($order->order_id);
                    }
                    else{
                        GenerateDeliveoInvoice::dispatch($order->deliveo_id);
                    }

                    dump(sprintf(
                        "Creating invoice for order: %s - %s",
                        $order->order_id ?? '',
                        $order->deliveo_id ?? ''
                    ));

                    $hasNewInvoice = true;
                }

            } else {
                if ($existing->last_modified != $apiOrder['last_modified']) {
                    $existing->update([
                        'order_id' => $apiOrder['referenceid'],
                        'last_modified' => $apiOrder['last_modified'],
                        'payload' => $apiOrder,
                    ]);

                }
            }
        }

        if (!$hasNewInvoice) {
            dump('No pending invoices yet...');
        }
    }
}
