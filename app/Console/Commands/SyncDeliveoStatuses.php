<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveoController;
use App\Http\Controllers\SalesRenderController;
use App\Models\Order;
use Illuminate\Console\Command;

class SyncDeliveoStatuses extends Command
{
    protected $signature = 'app:sync-deliveo-statuses';

    protected $description = 'Command description';

    public function handle()
    {
        $deliveoController = new DeliveoController();
        $failedOrders = $deliveoController->get_failed_orders();

        if (empty($failedOrders)) {
            \Log::info('No failed orders found.');
            return;
        }

        $salesRenderController = new SalesRenderController();
        $updatedOrders = [];

        $orders_count = 0;

        foreach ($failedOrders as $failedOrder) {
            //if($orders_count > 15) break;

            $deliveoId = $failedOrder->deliveo_id;
            $localOrder = Order::where('destination_id', $deliveoId)->first();

            // Skip if order already marked as Returned
            if (!$localOrder || $localOrder->status === 'Returned') {
                continue;
            }

            $previousStatus = $localOrder->status;

            $localOrder->status = 'Returned';
            $localOrder->save();

            $salesRenderController->update_order_status($localOrder->source_id, 8); //Returned = 8
            $orders_count++;

            $updatedOrders[] = [
                'sales_render_id' => $localOrder->source_id,
                'deliveo_id' => $localOrder->destination_id,
                'previous_status' => $previousStatus,
            ];

            $this->logOrderUpdate(end($updatedOrders));
        }

        if (!empty($updatedOrders)) {
            \Log::info('Order update summary:', $updatedOrders);
        }
    }

    protected function logOrderUpdate(array $data)
    {
        \Log::info("Order updated to Returned", $data);
    }

}

//21593
