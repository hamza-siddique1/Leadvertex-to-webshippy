<?php

namespace App\Http\Controllers;

use App\Models\BlockedUser;
use App\Models\Order;
use App\Models\ProductMapping;
use App\Notifications\LeadVertexNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Telegram\TelegramChannel;

class WebhookController extends Controller
{
    public function store(Request $request)
    {
        /*
         * This function get webhook from LV when order status is updated and send postback to keitaro
         */
        $data = $request->all();

        if ($data['status'] == 'accepted') {
            $keitarostatus = 'SALE';
        }
        elseif ($data['status'] == 'spam') {
            $keitarostatus = 'Rejected';
        }

        $data_array['to'] = 'keitaro';
        $data_array['msg'] = sprintf("Leadvertex order no. %s status updated to %s", $data['id'], $data['status']);

        try {
            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        } catch (\Exception $e) {
        }

        $domain = env('LEADVERTEX_API_URL');
        if($request->has('domain')){
             $domain = env('LEADVERTEX_API_URL2');
        }

        $url = sprintf("%s/getOrdersByIds.html?token=%s&ids=%d", $domain, env('TOKEN'), $data['id']);

        try {
            $response = Http::get($url);
            $response = json_decode($response);

            foreach ($response as $order) {
                if($order->utm_term != ''){
                    $utm_term = $order->utm_term;
                    $payout = 0; //$order->total;
                }
                else{
                    return response()->json(['UTM term not found']);
                }

                try{
                    //Send status update to Arknet
                    if($order->referer != ''){
                        if($order->referer == 'arknet'){
                            $arknetController = new ArkNetLeadsController();
                            $arknetController->send_status_update($utm_term, $data['status']);
                        }

                        if($order->referer == 'arbitrage_up'){
                            $arbitrageController = new ArbitrageUpLeadsController();
                            $arbitrageController->send_status_update($utm_term, $data['status']);
                        }

                        if($order->referer == 'darkleads'){
                            $darkleadController = new DarkLeadsController();
                            $darkleadController->send_status_update($utm_term, $data['status']);
                        }

                    }

                    $keitaro_url = sprintf("%s%s&status=%s&payout=%s", env('KEITARO_API_URL'), $utm_term, $keitarostatus, $payout);
                    Http::post($keitaro_url);

                }
                catch(\Exception $e){
                    return response()->json([$e->getMessage()]);
                }

            }
        } catch (\Exception $e) {
            $data_array['msg'] = $e->getMessage();
            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        }

    }

    public function store_old(Request $request)
    {
        return 0;
        /*
         * This function get webhook from LV when order status is "accepted", and creates new order on Webshippy
         */
        $data = $request->all();

        app('log')->channel('webhooks')->info($data);
        if ($data['status'] != 'accepted') {
            return;
        }

        $data_array['to'] = 'webshippy';
        $data_array['msg'] = sprintf("Leadvertex order no. %s status updated to ACCEPTED", $data['id']);

        try {
            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        } catch (\Exception $e) {
        }

        $url = sprintf("%s/getOrdersByIds.html?token=%s&ids=%d", env('LEADVERTEX_API_URL'), env('TOKEN'), $data['id']);

        try {
            $response = Http::get($url);
            // $billingo = new BillingoController();

            $response = json_decode($response);
            // $billingo->createInvoice($response);
            // dd('Stop');
            // This job is now done by telescope
//            ProductWebhook::create([
//                'product_id' => $data['id'],
//                'response' => $response
//            ]);

//            $json = file_get_contents(public_path('vertex.json'));
//            $response = json_decode($json);

            $subtotal = 0;
            $total_number_of_products = 0;
            foreach ($response as $order) {
                $products = [];

                foreach ($order->goods as $product) {
                    $product_sku = ProductMapping::where('product_id_lv', $product->goodID)->first();
                    if (!$product_sku) {
                        continue;
                    }

                    $products[] = [
                        'sku' => $product_sku->webshippy_sku,
                        'productName' => $product->name,
                        'priceGross' => $product->price,
                        'vat' => 0.27,
                        'quantity' => $product->quantity,
                    ];
                    $subtotal += $product->price * $product->quantity;
                    $total_number_of_products += $product->quantity;
                }

                if ($total_number_of_products > 2) {
                    $shippingPrice = 0;
                } elseif ($total_number_of_products == 2) {
                    $shippingPrice = 1500;
                } elseif ($total_number_of_products == 1) {
                    $shippingPrice = 3500;
                }

                $phoneNumber = $order->phone ?? '';
                if (strpos($phoneNumber, '36') === 0) {
                    $phoneNumber = '+' . $phoneNumber;
                }

                $request_body = [
                    'apiKey' => env('TOKEN'),
                    'order' => [
                        'referenceId' => "LV#" . $data['id'],
                        'createdAt' => $order->datetime,
                        'shipping' => [
                            'name' => $order->fio,
                            'email' => $order->email,
                            'phone' => $phoneNumber,
                            'countryCode' => $order->country,
                            'zip' => $order->postIndex,
                            'city' => $order->city,
                            'country' => $order->country,
                            'address1' => $order->address,
                            'note' => $order->comment,
                        ],
                        'billing' => [
                            'name' => $order->fio,
                            'email' => $order->email,
                            'phone' => $phoneNumber,
                            'countryCode' => $order->country,
                            'zip' => $order->postIndex,
                            'city' => $order->city,
                            'country' => $order->country,
                            'address1' => $order->address,

                        ],
                        'payment' => [
                            'paymentMode' => "cod",
                            'codAmount' => $subtotal,
                            'paymentStatus' => "pending",
                            'paidDate' => $order->lastUpdate,
                            "shippingPrice" => $shippingPrice,
                            'shippingVat' => 0,
                            'currency' => "HUF",
                            'discount' => 0,
                        ],
                        'products' => $products,
                    ],
                ];

                $request_body['order']['payment']['shippingPrice'] = $shippingPrice; //if quantity > 2, then set shipping price to 0
                $request_body['order']['payment']['codAmount'] += $shippingPrice;

                $url = sprintf("%s/CreateOrder/json", env('WEBSHIPPY_API_URL'));
                $request_body = ['request' => json_encode($request_body)];

                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->asForm()->post($url, $request_body);

                dump($response->json());

                app('log')->channel('webhooks')->info($response->json());

                $response = json_decode($response);
                WebshippyOrders::updateOrCreate([
                    'order_id' => $response->wspyId,
                ],
                    ['status' => 'new']
                );

                $data_array['msg'] = sprintf("Webshippy new order: %s", $response->wspyId);
                try {
                    Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
                } catch (\Exception $e) {
                }

                return $response;
            }
        } catch (\Exception $e) {
            $data_array['msg'] = $e->getMessage() . " WebhookController line 145";
            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        }

    }

    public function sendDataToVCC($name, $phone, $productName, $id, $date, $msg, $referer)
    {

        $data_array['to'] = 'comnica';

        if (strlen($phone) == 9) {
            $phone = "36" . $phone;
        }

        //If starting from 0, then append 3 at the begining
        if (strlen($phone) > 11) {
            $phone = substr($phone, -11);
        }

        if (substr($phone, 0, 1) === "0") {
            $phone = "3" . substr($phone, 1);
        }

        $msg .= "Phone: ";
        $msg .= $phone;
        $msg .= " ";
        $result = $msg;

        $data['form'] = [
            'name' => $name,
            'termek' => $productName,
            'order_id' => $id,
            'termek2' => $referer ?? ''
        ];

        $data['contacts']['1'] = [
            'title' => 'customer',
            'name' => $name,
            'phone' => $phone,
        ];

        $response = Http::withBasicAuth(env('VCC_USER'), env('VCC_PASS'))->post(env('VCC_API_URL') . '/projects/5/records', $data);
        //$response = file_get_contents(public_path('comnica.json'));

        $main_response = json_decode($response);
        #run loop on response->json and create string for each array element

        $data_array['msg'] = $result;
        Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        // DiscordAlert::message($result);

    }

    //New order Webhook directly comes to this method
    public function createRecordOnVCC(Request $request)
    {
        $data = $request->all();
        $msg = "";

        $data_array['to'] = 'comnica';
        $data_array['msg'] = "Leadvertex new order: " . $data['id'] . " ";
        Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        app('log')->channel('webhooks')->info($data);

        $domain = env('LEADVERTEX_API_URL');
        if($request->has('domain')){
             $domain = env('LEADVERTEX_API_URL2');
        }

        $url = sprintf("%s/getOrdersByIds.html?token=%s&ids=%d", $domain, env('TOKEN'), $data['id']);

        $response = Http::get($url);
        // $response = file_get_contents(public_path('vertex.json'));

        $response = json_decode($response);

        foreach ($response as $order) {
            $name = $order->fio;
            $phone = $order->phone;
            $referer= $order->referer;
            $productName = "";

            $isBlocked = BlockedUser::where('phone', $phone)->first();

            if ($isBlocked) {
                $response_id = $this->mark_as_spam_on_leadvertex($data['id']);

                if ($response_id == "OK") {
                    $data_array['to'] = 'comnica';
                    $data_array['msg'] = sprintf("Order %s marked as spam on Leadvertex: ", $data['id']);
                    Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
                    return 0;
                }

            }

            foreach ($order->goods as $product) {
                $productName .= $product->name . ',';
            }

        }

        // $this->sendData($name, $phone, $productName, $data['id'], $order->datetime, $msg);
        $this->sendDataToVCC($name, $phone, $productName, $data['id'], $order->datetime, $msg, $referer);

    }

    public function sendData($name, $phone, $productName, $id, $date, $msg)
    {

        $data_array['to'] = 'comnica';

        if (strlen($phone) == 9) {
            $phone = "36" . $phone;
        }

        //If starting from 0, then append 3 at the begining
        if (strlen($phone) > 11) {
            $phone = substr($phone, -11);
        }

        if (substr($phone, 0, 1) === "0") {
            $phone = "3" . substr($phone, 1);
        }

        $msg .= "Phone: ";
        $msg .= $phone;
        $msg .= " ";
        $result = $msg;

        $data = [
            'rq_sent' => '',
            'payload' => [
                'comments' => [],
                'contacts' => [
                    [
                        'active' => true,
                        'contact' => $phone,
                        'name' => '',
                        'preferred' => true,
                        'priority' => 1,
                        'source_column' => 'phone',
                        'type' => 'phone',
                    ],
                ],
                'custom_data' => [
                    'name' => $name,
                    'phone' => $phone,
                    'termek' => $productName,
                    'sp_id' => $id,
                    'date' => $date,
                ],
                'system_columns' => [
                    'callback_to_user_id' => null,
                    'dial_from' => null,
                    'dial_to' => null,
                    'manual_redial' => null,
                    'next_call' => null,
                    'priority' => 1,
                    'project_id' => 76,
                ],
            ],
        ];

        $response = Http::withBasicAuth(env('COMNICA_USER'), env('COMNICA_PASS'))->post(env('COMNICA_API_URL') . '/integration/cc/record/save/v1', $data);
        //$response = file_get_contents(public_path('comnica.json'));

        $main_response = json_decode($response);
        #run loop on response->json and create string for each array element

        if (isset($main_response->payload->errors)) {

            $responseArray = json_decode($response, true);
            foreach ($responseArray as $key => $value) {
                $result .= $key . ': ' . (is_array($value) ? json_encode($value) : $value) . ', ';
            }

            $result = rtrim($result, ', ');

            $result = substr($result, 0, 2000);
        } else {
            $result .= " Comnica ID: ";
            $result .= $main_response->payload->id;
        }

        $data_array['msg'] = $result;
        Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_array));
        // DiscordAlert::message($result);

    }

    public function mark_as_spam_on_leadvertex($lead_vertex_id)
    {
        $url = sprintf("%s/updateOrder.html?token=%s&id=%d", env('LEADVERTEX_API_URL'), env('TOKEN'), $lead_vertex_id);

        $request_body = [
            'status' => 9, // Spam/Errors
        ];
        $lv_response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post($url, $request_body);

        $lv_response = json_decode($lv_response);

        return $lv_response->$lead_vertex_id;
    }

    public function salesrender(Request $request)
    {
        $order = $request->all();

        $order_id = $order['id'];
        $status = data_get($order, 'status.name');
        $allowedStatuses = ['Accepted', 'Sent to deliveo'];
        if (!in_array($status, $allowedStatuses)) {
            return response()->json(['error' => 'Invalid status'], status: 200);
        }
        $createdAt = data_get($order, 'createdAt', now());

        $rawDeliveryDate = data_get($order, 'data.dateTimeFields.0.value');
        $deliveryTimestamp = $rawDeliveryDate ? \Carbon\Carbon::parse($rawDeliveryDate)->toDateTimeString() : null;

        $isDueTomorrow = $deliveryTimestamp
        ? Carbon::parse($deliveryTimestamp)->isSameDay(Carbon::tomorrow())
        : false;

        $isFridayToMonday = false;
        if ($deliveryTimestamp) {
            $deliveryDate = Carbon::parse($deliveryTimestamp);
            $createdDate = Carbon::parse($createdAt);

            if ($createdDate->isFriday() && $deliveryDate->isMonday()) {
                $isFridayToMonday = true;
            }
        }

        Order::updateOrCreate(
            ['source_id' => $order_id],
            [
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => now(),
                'delivery_date' => $deliveryTimestamp,
            ]
        );

        $deliveoController = new DeliveoController();

        // ✅ Rule 2: If delivery date is present, send only if status is Sent to deliveo
        if ($status === 'Sent to deliveo') {
            $deliveoController->create_shipment($order);
        }

        if(is_null($deliveryTimestamp)){
            $deliveoController->create_shipment($order);
        }
        else{
            // ✅ Rule 1: If status is Accepted, send even if delivery date is empty
            if ($status === 'Accepted' && $isDueTomorrow) {
                $deliveoController->create_shipment($order);
            }

            // ✅ Rule 3: If created Friday and delivery Monday
            if ($status === 'Accepted' && $isFridayToMonday) {
                $deliveoController->create_shipment($order);
            }
        }

        return response()->json(['success' => true]);
    }

}
