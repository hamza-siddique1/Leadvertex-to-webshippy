<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveoController;
use App\Http\Controllers\SalesRenderController;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTodayOrdersToDeliveo extends Command
{
    protected $signature = 'orders:send-today';
    protected $description = 'Send orders with delivery date as today to next CRM';

    public function handle()
    {
        $today = Carbon::now();

        if ($today->isWeekend()) {
            return;
        }

        $targetDate = $today->copy()->startOfDay();

        $orders = Order::whereDate('delivery_date', $targetDate)
            ->get();

        $deliveoController = new DeliveoController();
        $salesRenderController = new SalesRenderController();

        foreach ($orders as $order) {
            $order_data = $salesRenderController->get_order_info($order->source_id);

            if (!$order_data || !isset($order_data['id'], $order_data['status']['name'])) {
                return response()->json(['error' => 'Invalid order structure'], 422);
            }

            $order_id = $order_data['id'];
            $status = data_get($order_data, 'status.name');
            $createdAt = data_get($order_data, 'createdAt', now());

            $rawDeliveryDate = data_get($order_data, 'data.dateTimeFields.0.value');
            $deliveryTimestamp = $rawDeliveryDate ? \Carbon\Carbon::parse($rawDeliveryDate)->toDateTimeString() : null;

            Order::updateOrCreate(
                ['source_id' => $order_id],
                [
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                    'delivery_date' => $deliveryTimestamp,
                ]
            );

            if($order->destination_id){
                app('log')->channel('resent_orders')->info(sprintf("Salesrender: %s - Deliveo: %s", $order_id, $order->destination_id));
            }

            $deliveoController->create_shipment($order_data);
            dump("Sending order ID {$order->source_id} with delivery_date: {$order->delivery_date}");
        }

        dump("Total orders sent: " . $orders->count());
    }
}
