<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveoController;
use App\Http\Controllers\SalesRenderController;
use App\Models\Order;
use Illuminate\Console\Command;

class SyncSuccessDeliveoStatuses extends Command
{
    protected $signature = 'app:sync-success-deliveo-statuses';

    protected $description = 'Command description';

    public function handle()
    {
        $deliveoController = new DeliveoController();
        $successfulOrders = $deliveoController->get_delivered_orders();

        if (empty($successfulOrders)) {
            \Log::info('No failed orders found.');
            return;
        }

        $salesRenderController = new SalesRenderController();
        $updatedOrders = [];

        $orders_count = 0;

        foreach ($successfulOrders as $successfulOrder) {
            // if($orders_count > 20) break;

            if (empty($successfulOrder->received_by)) {
                continue;
            }

            $deliveoId = $successfulOrder->deliveo_id;
            $localOrder = Order::where('destination_id', $deliveoId)->first();

            // Skip if order already marked as Delivered
            if (!$localOrder || $localOrder->status === 'Delivered') {
                continue;
            }

            $previousStatus = $localOrder->status;

            $localOrder->status = 'Delivered';
            $localOrder->save();

            $salesRenderController->update_order_status($localOrder->source_id, 7); //Delivered = 8
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
