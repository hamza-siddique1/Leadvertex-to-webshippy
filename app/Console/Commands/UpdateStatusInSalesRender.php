<?php

namespace App\Console\Commands;

use App\Http\Controllers\SalesRenderController;
use App\Models\DeliveoSyncLog;
use Illuminate\Console\Command;

class UpdateStatusInSalesRender extends Command
{
    protected $signature = 'salesrender:update-status';
    protected $description = 'Update status in salesrender';

    public function handle()
    {
        $orders = DeliveoSyncLog::where('sync_status', 'matched')->limit(18)->get();

        if ($orders->isEmpty()) {
            $this->info("No pending orders to update.");
            return;
        }

        $salesRender = new SalesRenderController();

        foreach ($orders as $order) {
            $sales_render_id = $order->salesrender_order_id;
            $this->info("Processing order: {$sales_render_id}");

            $salesRender->update_order_status($sales_render_id, 7); //7 = Delivered

            $order->update([
                'sync_status' => 'updated',
            ]);

            app('log')->channel('update_status')->info(sprintf("SR: %s - Deliveo: %s - ID: %s", $sales_render_id, $order->deliveo_id, $order->id));

        }
    }
}
