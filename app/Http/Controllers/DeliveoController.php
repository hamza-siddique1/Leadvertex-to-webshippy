<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAndUploadInvoice;
use App\Models\APILog;
use App\Models\Order;
use App\Notifications\LeadVertexNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class DeliveoController extends Controller
{
    public function webhook(Request $request){
        $deliveo_id = $request['deliveo_id'];

        $url = sprintf("%s/package/%s?licence=naturprime&api_key=%s", env('DELIVEO_BASE_URL'), $deliveo_id, env('DELIVEO_API_KEY'));

        // $jsonFilePath = public_path('deliveo.json');
        // $response = File::get($jsonFilePath);

        $response = Http::timeout(30)->get($url);

        $response = json_decode($response);

        if($response->type != 'success') return;

        $szamlacontroller = new SzamlaController();
        $szamlacontroller->create_invoice($response);
    }

    public function get_product_details($item_id){

        $url = sprintf("%s/item/%s?licence=naturprime&api_key=%s", env('DELIVEO_BASE_URL'), $item_id, env('DELIVEO_API_KEY'));

        // $jsonFilePath = public_path('deliveo.json');
        // $response = File::get($jsonFilePath);

        $response = Http::timeout(30)->get($url);

        $response = json_decode($response);
        if ($response->type != 'success') {
            return null;
        } else {
            return $response->data[0];
        }
    }

    public function create_shipment($data)
    {
        $order_id = $data['id'];
        $orderFromDB = Order::where('source_id', $order_id)->first();
        if($orderFromDB->destination_id != null) return; //already sent to deliveo

        $url = sprintf(
            "%spackage/create?licence=%s&api_key=%s",
            env('DELIVEO_BASE_URL'),
            env('DELIVEO_LICENCE'),
            env('DELIVEO_API_KEY')
        );

        $deliveo_data = $this->transform($data);

        $apiLogData = [
            'order_id' => data_get($data, 'data.ordersFetcher.orders.0.id'),
            'crm' => 'Deliveo',
            'api_url' => $url,
            'request_method' => 'POST',
            'request_body' => $deliveo_data,
        ];

        try {

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post($url, $deliveo_data);

            $apiLogData['status'] = $response->status();
            $apiLogData['response_body'] = $response->json();


            if ($response->failed()) {
                throw new \Exception('Failed to create shipment with Deliveo. Status code: ' . $response->status());
            }

            $json = $response->json();

            if ($json['type'] === 'success' && isset($json['data'][0])) {
                $shipmentId = $json['data'][0]; // e.g. MXP25050250632

                if ($shipmentId) {
                    Order::where('source_id', $order_id)->update([
                        'destination_id' => $shipmentId,
                    ]);
                }

                GenerateAndUploadInvoice::dispatch($order_id);

                $dateFolder = now()->format('d-m-Y');
                $fileName = 'Invoice_' . $order_id . '.pdf';

                $data_telegram['to'] = 'salesrender';
                $data_telegram['msg'] = sprintf("Order %s sent to Deliveo: %s", $order_id, $shipmentId);
                $data_telegram['order_id'] = sprintf(
                    "https://asperminw.com/invoices/download?folder=%s&file=%s",
                    $dateFolder,
                    $fileName
                );
                Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_telegram));
            }

        } catch (\Exception $e) {
            $apiLogData['response_body'] = 'Error: ' . $e->getMessage();
        } finally {
            APILog::create($apiLogData);
        }
    }

    public function transform(array $webhookData): array
    {
        $firstName = Arr::get($webhookData, 'data.humanNameFields.0.value.firstName', '');
        $lastName = Arr::get($webhookData, 'data.humanNameFields.0.value.lastName', '');

        $phoneRaw = Arr::get($webhookData, 'data.phoneFields.0.value.raw', '');
        $postcode = Arr::get($webhookData, 'data.addressFields.0.value.postcode', '');

        $city = Arr::get($webhookData, 'data.addressFields.0.value.city', '');
        $address_1 = Arr::get($webhookData, 'data.addressFields.0.value.address_1', '');
        $address_2 = Arr::get($webhookData, 'data.addressFields.0.value.address_2', '');

        $apartment = Arr::get($webhookData, 'data.addressFields.0.value.apartment', '');
        $country = Arr::get($webhookData, 'data.addressFields.0.value.country', 'HU');

        $totalCodValue = $this->calculateTotalCodValue($webhookData);

        $transformedData = [
            'sender' => 'Supreme Pharmatech Europe s.r.o.',
            'sender_country' => 'SK',
            'sender_zip' => '94501',
            'sender_city' => 'Komárno',
            'sender_address' => 'Senný trh 3116/7',
            'sender_phone' => '36304374237',
            'sender_email' => 'szabovk@supremepharmatech.hu',
            'consignee' => trim($firstName . ' ' . $lastName),
            'consignee_country' => $country ?? 'HU',
            'consignee_zip' => $postcode ?? '',
            'consignee_city' => $city ?? '',
            'consignee_address' => Str::limit(sprintf('%s %s', $address_1, $address_2), 40, '') ,
            'consignee_apartment' => $apartment ?? '',
            'consignee_phone' => $phoneRaw ?? '',

            'delivery' => 89, //89: FámaFutár , 185: FoxPost //change to FámaFutár
            'cod' => $totalCodValue,
        ];

        $transformedData['packages'] = $this->transformPackages($webhookData);

        return $transformedData;
    }

    private function calculateTotalCodValue(array $webhookData): float
    {
        $total = 0.0;

        // Calculate total for cart items
        foreach (Arr::get($webhookData, 'cart.items', []) as $cartItem) {
            $total += (float)Arr::get($cartItem, 'pricing.totalPrice', 0);
        }

        // Calculate total for promotion items
        foreach (Arr::get($webhookData, 'cart.promotions', []) as $promotion) {
            foreach (Arr::get($promotion, 'items', []) as $item) {
                // Add the unitPrice of the promotion item
                $total += (float)Arr::get($item, 'pricing.unitPrice', 0);
            }
        }

    return $total;

    }
    private function transformPackages(array $webhookData): array
    {
        $packages = [];

        foreach (Arr::get($webhookData, 'cart.promotions', []) as $promotion) {
            $packages[] = [
                'customcode' => $promotion['promotion']['name'],
                'item_no' => $promotion['promotion']['id'],
                'weight' => 1.2,
                ];
        }

        foreach (Arr::get($webhookData, 'cart.items', []) as $cartItem) {
            $item_id = $cartItem['sku']['item']['id'];
            if($item_id == '15') continue;

            $packages[] = [
              'customcode' => $cartItem['sku']['item']['name'],
              'item_no' => $item_id,
              'weight' => 1.5,
              ];
        }

        return $packages;
    }

    public function get_failed_orders()
    {
        $lastModified = now()->subDays(30)->format('Y-m-d H:i:s');

        $url = sprintf(
            "%spackage?licence=%s&api_key=%s&filter[unsuccessful_id][nnull]=&filter[last_modified][g]=%s&limit=300",
            env('DELIVEO_BASE_URL'),
            env('DELIVEO_LICENCE'),
            env('DELIVEO_API_KEY'),
            urlencode($lastModified)
        );

        $response = Http::timeout(30)->get($url);

        $response = json_decode($response);

        if ($response->type != 'success') {
            return null;
        } else {
            return $response->data;
        }
    }

    public function get_delivered_orders()
    {
        $lastModified = now()->subDays(30)->format('Y-m-d H:i:s');

        $url = sprintf(
            "%spackage?licence=%s&api_key=%s&filter[unsuccessful_id][null]=&filter[last_modified][g]=%s&limit=300",
            env('DELIVEO_BASE_URL'),
            env('DELIVEO_LICENCE'),
            env('DELIVEO_API_KEY'),
            urlencode($lastModified)
        );

        $response = Http::timeout(30)->get($url);

        $response = json_decode($response);

        if ($response->type != 'success') {
            return null;
        } else {
            return $response->data;
        }
    }
}

