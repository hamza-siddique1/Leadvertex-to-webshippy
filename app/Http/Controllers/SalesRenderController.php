<?php

namespace App\Http\Controllers;

use App\Notifications\LeadVertexNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class SalesRenderController extends Controller
{
    public function get_order_info($order_id)
    {
        $query = <<<GQL
query GetOrderById(\$orderId: ID!) {
  ordersFetcher(filters: { include: { ids: [\$orderId] } }) {
    orders {
      id
      status { name }
      createdAt
      data {
        dateTimeFields { value }
        humanNameFields { value { firstName lastName } }
        phoneFields { value { raw } }
        addressFields { value {
          postcode region city address_1 address_2 building apartment country
          location { latitude longitude }
        }}
      }
      cart {
        items {
          id
          sku {
            item { id name }
            variation { number property }
          }
          quantity
          pricing { unitPrice totalPrice }
        }
        promotions {
          id
          promotion { id name }
          items {
            promotionItem
            sku {
              item { id name }
              variation { number property }
            }
            pricing { unitPrice }
          }
        }
      }
    }
  }
}
GQL;

        $cacheKey = 'order_' . $order_id;

        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($order_id, $query) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('GRAPHQL_API_TOKEN'),
            ])->post(env('GRAPHQL_API_URL'), [
                'query' => $query,
                'variables' => ['orderId' => $order_id],
            ]);

            return $response->json('data.ordersFetcher.orders.0');
        });
    }

    public function create_invoice($orderid)
    {
       $order = $this->get_order_info($orderid);

      // Initialize data array
      $data = [
        'invoice_id' => $order['id'] ?? null,
        'seller_name' => 'Supreme Pharmatech Europe s.r.o.',
        'seller_address_line1' => '945 01 Komárno',
        'seller_address_line2' => 'Senný trh 3116/7',
        'seller_city_zip' => '1082',
        'seller_country' => 'Slovakia',
        'seller_tax_id' => 'SK2122214820',
        'seller_company_reg_id' => '56139471',
        'footer_legal_text_1' => 'A számla tartalma mindenben megfelel a hatályos',
        'footer_legal_text_2' => 'törvényekben foglaltaknak',
        'buyer_name' => '',
        'buyer_phone' => '',
        'buyer_address_line1' => '',
        'buyer_address_line2' => '',
        'buyer_city_zip' => '',
        'buyer_country' => 'Slovakia',
        'invoice_date' => '',
        'due_date' => '',
        'fulfillment_date' => '',
        'order_id' => '',
        'item_name_1' => '',
        'item_sub_description_1' => '',
        'item_quantity_1' => 0,
        'item_unit_price_net_1' => '0,00',
        'item_total_price_net_1' => '0,00',
        'item_vat_rate_1' => 27,
        'item_total_price_gross_1' => '0,00',
        'subtotal_net' => 0,
        'vat_rate_summary' => 27,
        'total_vat_amount' => 0,
        'grand_total_summary' => 0,
        'grand_total_amount' => '0',
      ];

      // Safe extraction of buyer name
      $nameField = $order['data']['humanNameFields'][0]['value'] ?? null;
      if ($nameField) {
          $data['buyer_name'] = trim(($nameField['lastName'] ?? '') . ' ' . ($nameField['firstName'] ?? ''));
      }

      // Phone number
      $data['buyer_phone'] = $order['data']['phoneFields'][0]['value']['raw'] ?? null;

      // Address
      $address = $order['data']['addressFields'][0]['value'] ?? [];
      if (!empty($address)) {
          $data['buyer_address_line1'] = $address['city'] ?? '';
          $data['region'] = $address['region'] ?? '';
          $data['buyer_address_line2'] = implode(' ', array_filter([
              $address['address_1'] ?? '',
              $address['address_2'] ?? '',
          ]));
          $data['buyer_city_zip'] = $address['postcode'] ?? '';
          $data['buyer_country'] = $address['country'] ?? 'Magyarország';
      }

      // Dates
      $createdAt = $order['createdAt'] ?? null;
      if ($createdAt) {
          $data['invoice_date'] = date('Y. m. d.', strtotime($createdAt));
          $data['due_date'] = date('Y. m. d.', strtotime($createdAt));
      }

      $data['fulfillment_date'] = null;
      $fulfillmentDate = $order['data']['dateTimeFields'][0]['value'] ?? null;
      if ($fulfillmentDate) {
          $data['fulfillment_date'] = date('Y. m. d.', strtotime($fulfillmentDate));
      }

      // Order ID
      $data['order_id'] = $order['id'] ?? null;

      // Item (if available)
      // Items loop
    $items = $order['cart']['items'] ?? [];
    $promotions = $order['cart']['promotions'] ?? [];
    $allItems = array_merge($items, $promotions);

    $subtotalNet = 0;
    $totalVat = 0;
    $grandTotal = 0;
    $vatRate = 0.27;

    $itemsData = [];

    foreach ($items as $item) {
      $name = $item['sku']['item']['name'] ?? 'Unknown Item';
      $quantity = $item['quantity'] ?? 1;
      $unitPrice = $item['pricing']['unitPrice'] ?? 0;
      $totalPrice = $item['pricing']['totalPrice'] ?? 0;
      $net = round($totalPrice / (1 + $vatRate), 2);
      //$vat = $totalPrice - $net;

      $itemsData[] = [
        'name' => $name,
        'description' => '',
        'quantity' => $quantity,
        'unit_price_net' => number_format($unitPrice , 2, ',', ''),
        'total_price_net' => number_format($net, 2, ',', ''),
        'vat_rate' => 27,
        'total_price_gross' => number_format($totalPrice, 2, ',', ''),
      ];

//      $subtotalNet += $net;
//      $totalVat += $vat;
      $grandTotal += $totalPrice;
    }

    foreach ($promotions as $promotion) {
      $promotionName = $promotion['promotion']['name'] ?? 'Unknown Promotion';

      foreach ($promotion['items'] as $item) {
          $quantity = $item['promotionItem'] ?? 1;
          $unitPrice = $item['pricing']['unitPrice'] ?? 0;
          $totalPrice = $unitPrice * $quantity;
          $net = round($totalPrice / (1 + $vatRate), 2);
          //$vat = $totalPrice - $net;

          $itemsData[] = [
              'name' => $promotionName,
              'description' => $promotionName,
              'quantity' => $quantity,
              'unit_price_net' => number_format($unitPrice, 2, ',', ''),
              'total_price_net' => number_format($net, 2, ',', ''),
              'vat_rate' => 27,
              'total_price_gross' => number_format($totalPrice, 2, ',', ''),
          ];

//          $subtotalNet += $net;
//          $totalVat += $vat;
          $grandTotal += $totalPrice;
      }
    }

    $data['items'] = $itemsData;
    $data['grand_total'] = $grandTotal;

    $dateFolder = Carbon::now()->format('d-m-Y');
    $fileName = sprintf('Invoice_%s.pdf', $data['order_id']);

    $localFolderPath = storage_path('app/invoices/' . $dateFolder);
    $localFilePath = $localFolderPath . '/' . $fileName;

    \File::ensureDirectoryExists($localFolderPath);

    $html = view('pages.template.invoice', $data)->render();
    Pdf::loadHtml($html)
        ->setOption(['isRemoteEnabled' => true])
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isPhpEnabled', true)
        ->setPaper('a4')
        ->save($localFilePath);
}

    public function update_order_status($orderId, $statusId, $attempt = 1)
    {
        $mutation = <<<GQL
            mutation UpdateOrderStatus(\$input: UpdateOrderInput!) {
              orderMutation {
                updateOrder(input: \$input) {
                  id
                  status {
                    id
                    name
                  }
                }
              }
            }
            GQL;

        $variables = [
            'input' => [
                'id' => (string) $orderId,
                'statusId' => (string) $statusId,
            ],
        ];

        $result = $this->callGraphQL($mutation, $variables);

        if ($this->isOrderLocked($result) && $attempt === 1) {
            \Log::warning("Order locked. Attempting unlock...", ['order_id' => $orderId]);

            $unlockResult = $this->force_unlock_order($orderId);

            $unlockSuccess = $unlockResult['data']['lockMutation']['unlockEntity'] ?? false;

            if ($unlockSuccess) {
                \Log::info("Unlock successful. Retrying status update.", ['order_id' => $orderId]);
                return $this->update_order_status($orderId, $statusId, 2);
            } else {
                \Log::error("Unlock failed. Not retrying update.", ['order_id' => $orderId]);
                return null;
            }
        }

        $updatedOrder = $result['data']['orderMutation']['updateOrder'] ?? null;

        if ($updatedOrder) {
            // ✅ Status updated successfully — Send Telegram notification
            $data_telegram = [
                'to' => 'salesrender',
                'msg' => sprintf("Order %s status updated to: %s", $updatedOrder['id'], $updatedOrder['status']['name']),
            ];

            Notification::route(TelegramChannel::class, '')->notify(new LeadVertexNotification($data_telegram));
        } else {
            \Log::error('Failed to update order status', [
                'order_id' => $orderId,
                'status_id' => $statusId,
                'errors' => $result['errors'] ?? [],
            ]);
        }

        return $updatedOrder;
    }

    protected function callGraphQL(string $query, array $variables = [])
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env('GRAPHQL_API_TOKEN'),
        ])->post(env('GRAPHQL_API_URL'), [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            \Log::error('GraphQL request failed', [
                'query' => $query,
                'variables' => $variables,
                'response' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    public function force_unlock_order($orderId)
    {
        $mutation = <<<GQL
            mutation ForceUnlockOrder(\$input: UnlockInput!) {
              lockMutation {
                unlockEntity(input: \$input)
              }
            }
            GQL;

        $variables = [
            'input' => [
                'entity' => [
                    'entity' => 'Order',
                    'id' => (string) $orderId,
                ],
            ],
        ];

        $result = $this->callGraphQL($mutation, $variables);

        return $result['lockMutation']['unlockEntity'] ?? null;
    }

    private function isOrderLocked(array $result): bool
    {
        return !empty($result['errors'][0]['extensions']['code']) &&
            $result['errors'][0]['extensions']['code'] === 'ERR_ORDER_LOCKED';
    }
}
