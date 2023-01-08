<?php

namespace Ahly\Payments\Helper;

class Accepting
{
    const ACCEPT_SERVER = "https://test-nbe.gateway.mastercard.com/api/rest/version/56/merchant/";
    private $merchant;
    private $error;
    private $help;
    private $auth_session;
    private $shipping;
    private $billing;
    private $amount_cents;
    private $order_items;
    private $order_currency;
    private $order_unique_id;
    private $integration_id;
    private $payment_token;
    private $has_items;
    private $has_delivery;
    private $handles_shipping;
    private $cents;
    private $base_url;
    private $config;
    
    public function __construct(
        \Magento\Sales\Model\Order $order,
        array $config,
        $base_url
    ) {
        $this->order                = $order;
        //$this->auth_session           = $this->request_session($config['api_user'], $config['api_password']);
        $this->config = $config;
        $this->base_url = $base_url;


        //$this->set_order_data();

        $this->help = [
            "DEFAULT"  => [
                "Sorry, something went wrong please contact the store owner.",
            ],
            "PHONE"    => [
                "Wallet phone number must be a valid phone.",
            ],
        ];
    }

    
    public function set_session(){
        $this->auth_session  = $this->request_session($this->config['api_user'], $this->config['api_password']);
    }

    public function get_session()
    {
        return $this->auth_session;
    }

    public function get_error()
    {
        $error = "";

        if (is_string($this->error) && $this->error != '') {
            $error = "<p>$this->error</p>";
        } else if (is_array($this->error) && !empty($this->error)) {
            foreach ($this->error as $text) {
                $error .= "<p>$text</p>";
            }
        } else {
            $error = "<p><code>ERROR_CODE: EMPTY_REASONS</code></p>";
        }

        return $error;
    }

    public function get_error_response($short_msg, $target_help)
    {
        $helpers = "";
        foreach ($this->help[$target_help] as $tip) {
            $helpers .= "<p>$tip</p>";
        }

        return "<p>$short_msg</p>" . $this->get_error() . $helpers;
    }

    public function request_session($api_user, $api_password)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/nbe.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        //$response_url = $this->base_url . 'checkout/onepage/failure';
        $returnUrl = $this->base_url . 'ahly_pay/callback/online?orderid=' . $this->order->getIncrementId() . '&cancel=false&timeout=false';
        $cancelUrl = $this->base_url . 'ahly_pay/callback/online?orderid=' . $this->order->getIncrementId() . '&cancel=true&timeout=false';
        $timeoutUrl = $this->base_url . 'ahly_pay/callback/online?orderid=' . $this->order->getIncrementId() . '&cancel=false&timeout=true';
        
        $logger->info("start request session..");
        $data = [
            "apiOperation" => "CREATE_CHECKOUT_SESSION",
            "interaction" => [
                "operation" => "PURCHASE",
                "returnUrl" => $returnUrl,
                "cancelUrl" => $cancelUrl,
                "timeoutUrl" => $timeoutUrl,
                "merchant" => [
                    "name" => "TESTEGPTEST",
                    "address" => [
                        "line1" => "str1",
                        "line2" => "str2"
                    ]
                ]
            ],
            "order" => [
                "currency" => "EGP",
                "id" => $this->order->getIncrementId(),
                "amount" => $this->order->getGrandTotal(),
                "description" => "my product"
            ]
        ];

        //$data    = ['api_key' => $api_key];
        $result = $this->request('auth/tokens', $data, $api_user, $api_password, "POST");

        $logger->info("after request session Http..");

        if ($result) {
            // $this->merchant = $result->profile->id;
            $logger->info("not empty session ..");
            $logger->info($result->session->id);
            $this->order->setData('nbe_successIndicator', $result->successIndicator)->save();
            return $result->session->id;
        } else {
            $logger->info("empty session ..");
            return false;
        }
    }



    private function request($command, $data, $user, $pass, $method = 'POST')
    {

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/nbe.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $user_api = explode('.', $this->config['api_user'])[1];

        $ch  = curl_init();
       // $url = "https://test-nbe.gateway.mastercard.com/api/rest/version/57/merchant/TESTEGPTEST/session"; //self::ACCEPT_SERVER . $command;
        $url = "https://test-nbe.gateway.mastercard.com/api/rest/version/61/merchant/" . $user_api ."/session";
        $logger->info($url);
        $logger->info($user);
        $logger->info($pass);
        $logger->info($method);
        //if ($this->auth_token) {$url .= "?token=" . $this->auth_token;}
        if ($method == 'GET') {
            $url .= '&' . http_build_query($data);
        } else {
            $data = json_encode($data);
        }
        // add my code
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("$user:$pass")
        );


        $options = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER         => false,
            // CURLOPT_HTTPHEADER     => array("Content-Type: application/json"),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
        );
        if ($method == 'POST') {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = $data;
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $logger->info($code);
        $this->error = curl_error($ch);
        curl_close($ch);
        $result = false;
        if (($code == 200 || $code == 201) && $response) {

            $result = json_decode($response);

            $error  = false;
        } else if ($code != 0) {
            if ($response) {

                $response    = json_decode($response, true);
                $error       = $this->errorParser($response);
                $this->error = $error;
                $logger->info($this->error);
            } else {
                $this->error = 'ErrorCode: ' . $code;
                $logger->info($this->error);
            }
        }
        $logger->info($result);
        return $result;
    }

    private function errorParser($array)
    {
        $result = '';
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($key !== 0) {
                    $result .= $key . ': ';
                }
                if (is_array($value)) {
                    $result .= $this->errorParser($value) . "\n";
                } else {
                    $result .= $value;
                }
            }
        } else {
            $result = $array;
        }
        return $result;
    }
}
