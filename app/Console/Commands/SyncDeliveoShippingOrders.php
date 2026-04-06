<?php

namespace App\Console\Commands;

use App\Models\DeliveoSyncLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncDeliveoShippingOrders extends Command
{
    protected $signature = 'import-orders {filename}';
    protected $description = 'Import Deliveo CSV and prepare for Salesrender sync';

    public function handle()
    {
        $filename = $this->argument('filename');
        $path = storage_path('app/' . $filename);

        if (!file_exists($path)) {
            $this->error("File not found at: {$path}");
            return;
        }

        $file = fopen($path, 'r');
        $header = fgetcsv($file); // Skip the header row

        $count = 0;
        $this->info("Starting import...");

        while (($row = fgetcsv($file)) !== FALSE) {
            $deliveoId = $row[0];
            $phone = $this->normalizePhone($row[9]);
            $status = $row[2];
            $date = $row[4] ? Carbon::parse($row[4]) : null;
            $name = $row[5] ?? null;

            // Use updateOrCreate to prevent duplicate rows for the same Deliveo ID
            DeliveoSyncLog::updateOrCreate(
                ['deliveo_id' => $deliveoId],
                [
                    'phone_number'   => $phone,
                    'deliveo_status' => $status,
                    'delivery_date'  => $date,
                    'order_amount'  => $row[13] ?? null,
                    'customer_name'  => $name,
                    // 'sync_status'    => 'pending' // Reset status if re-importing
                ]
            );
            $count++;
        }

        fclose($file);
        dump("Imported {$count} records successfully.");
    }

    /**
     * Strip special characters from phone numbers to make searching easier
     */
    private function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
