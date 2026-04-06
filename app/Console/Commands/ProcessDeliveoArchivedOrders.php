<?php

namespace App\Console\Commands;

use App\Http\Controllers\SalesRenderController;
use App\Models\DeliveoSyncLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ProcessDeliveoArchivedOrders extends Command
{
    protected $signature = 'salesrender:match';
    protected $description = 'Fetch orders from Salesrender and identify the correct match';

    public function handle()
    {
        $logs = DeliveoSyncLog::where('sync_status', 'pending')->limit(5000)->get();

        if ($logs->isEmpty()) {
            $this->info("No pending logs to process.");
            return;
        }

        $salesRender = new SalesRenderController();

        foreach ($logs as $log) {
            $this->info("Processing phone: {$log->phone_number}");

            $query = <<<GRAPHQL
            query MyQuery(\$phone: [String]!) {
                ordersFetcher(
                    filters: {
                        include: {
                            data: { phoneFields: { filter: \$phone } },
                            statusIds: "6"
                        }
                    }
                ) {
                    orders {
                        id
                        status {
                            id
                            name
                        }
                        data {
                            phoneFields {
                                value {
                                    raw
                                }
                            }
                            dateTimeFields {
                                value
                            }
                        }
                        cart {
                            pricing {
                                price
                            }
                        }
                    }
                }
            }
            GRAPHQL;

            //$result = $salesRender->callGraphQL($query, ['phone' => $log->phone_number]);

            // Store API response for transparency
            //$log->update(['api_response' => json_encode($result)]);

            $result = json_decode($log->api_response, true);

            if (!$result || !isset($result['data'])) {
                $log->update([
                    'sync_status' => 'failed',
                    'error_message' => 'API Connection Failure or Invalid Response Structure'
                ]);
                $this->error("   - Connection error for {$log->phone_number}");
                continue;
            }

            $orders = $result['data']['ordersFetcher']['orders'];

            if (empty($orders)) {
                $log->update([
                    'sync_status' => 'not_found',
                    'error_message' => null, // Clear any previous error messages
                    'api_response' => json_encode([]) // Log that we got an empty array back
                ]);
                $this->warn("   - No shipping orders found for {$log->phone_number}");
                continue;
            }

            if (count($orders) === 1) {
                // Perfect Match
                $order = $orders[0];

                $srPrice = $order['cart']['pricing']['price'];
                if ($log->order_amount == $srPrice) {
                    $log->update([
                        'salesrender_order_id' => $order['id'],
                        'sync_status' => 'matched'
                    ]);
                    $this->info("Phone {$log->phone_number}: Matched ID {$order['id']}");
                }
                else{
                    $log->update([
                        'salesrender_order_id' => null,
                        'sync_status' => 'not_matched'
                    ]);
                    $this->warn("Phone $log->phone_number not matched");
                }

            } else {
                // Multiple orders are still in shipping!
                // We pick the one with the closest date to the Deliveo date

                $bestMatch = $this->findOrderByPrice($orders, $log->order_amount);
                if($bestMatch){
                    $log->update([
                        'salesrender_order_id' => $bestMatch['id'],
                        'sync_status' => 'matched',
                        'error_message' => 'Multiple shipping orders found. Picked by amount.'
                    ]);

                    $this->comment("Phone {$log->phone_number}: Multiple found, matched closest date.");
                }
                else{
                    $log->update([
                        'sync_status' => 'not_matched',
                    ]);
                }


            }
        }
    }

    private function findOrderByPrice($orders, $deliveo_price)
    {
        return collect($orders)->first(function ($order) use ($deliveo_price) {
            // Get price from the Salesrender structure
            $srPrice = $order['cart']['pricing']['price'] ?? 0;

            // Use (float) to ensure comparisons like "20300.00" == 20300 work correctly
            return (float)$srPrice === (float)$deliveo_price;
        });
    }
}
