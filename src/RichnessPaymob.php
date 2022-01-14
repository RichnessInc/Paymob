<?php


namespace Richness\Paymob;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class RichnessPaymob
{
    public function submitPayment($paymentMethod, $totalAmount, $itemsArr = [])
    {
        $items = $this->get_items($itemsArr);
        $token = $this->get_token();
        $order = $this->create_order($token, $items, $totalAmount);
        if ($paymentMethod == 'visa') {
            $paymentToken = $this->getPaymentToken($order, $token, config('paymobconfig.PAYMOB_CREDIT_INTEGRATION_ID'), $totalAmount);
            return Redirect::away('https://accept.paymob.com/api/acceptance/iframes/'.config('paymobconfig.IFRAME_ID').'?payment_token=' . $paymentToken);
        } elseif ($paymentMethod == 'wallet') {
            $paymentToken = $this->getPaymentToken($order, $token, config('paymobconfig.PAYMOB_WALLET_INTEGRATION_ID'), $totalAmount);
            $response = Http::post('https://accept.paymob.com/api/acceptance/payments/pay', [
                'source' => [
                    'identifier' => $this->wallet_number,
                    'subtype' => 'WALLET'
                ],
                'payment_token' => $paymentToken
            ]);
            return Redirect::away($response->object()->redirect_url);
        } else {
            throw new \Exception('Invalid Prams in submitPayment()');
        }
    }

    // RichnessInc/Paymob
    /*
     * @prams [$items => Must be array]
     * Array structure ['name', 'price', 'qty']
     * */
    public function get_items($items = []): array
    {
        if (!empty($items)) {
            foreach ($items as $item) {
                $thisItem = [
                    "name" => $item['name'],
                    "amount_cents" => ceil($item['price'] * 100),
                    "description" => $item['name'],
                    "quantity" => $item['qty']
                ];
                $items[] = $thisItem;
            }
        }
        return $items;
    }

    // Get Auth Token From Pay mob Payment Getaway
    public function get_token()
    {
        $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => config('paymobconfig.PAYMOB_API_KEY')
        ]);

        return $response->object()->token;
    }

    public function create_order($token, $items, $totalAmount)
    {
        $data = [
            "auth_token" => $token,
            "delivery_needed" => false,
            "amount_cents" => $totalAmount * 100,
            "currency" => "EGP",
            "items" => $items
        ];
        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', $data);
        return $response->object();
    }

    public function getPaymentToken($order, $token, $integration_id, $totalAmount)
    {

        $billing_data = [
            "apartment" => "NA",
            "email" => "NA",
            "floor" => "NA",
            "first_name" => "NA",
            "street" => "NA",
            "building" => "NA",
            "phone_number" => "NA",
            "shipping_method" => "NA",
            "postal_code" => "NA",
            "city" => "NA",
            "country" => "NA",
            "last_name" => "NA",
            "state" => "NA"
        ];

        $data = [
            "auth_token" => $token,
            "amount_cents" => $totalAmount * 100,
            "expiration" => 3600,
            "order_id" => $order->id,
            "billing_data" => $billing_data,
            "currency" => "EGP",
            "integration_id" => $integration_id
        ];

        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', $data);

        return $response->object()->token;

    }
}
