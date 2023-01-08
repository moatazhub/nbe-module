<?php

 const ACCEPT_SERVER = "https://test-nbe.gateway.mastercard.com/api/";
 $user = "merchant.TESTEGPTEST";
 $pass = "c622b7e9e550292df400be7d3e846476";

 function request($command, $data = array(), $user, $pass, $method = 'POST')
    {
        $ch  = curl_init();
        $url = ACCEPT_SERVER . $command;
        //if ($this->auth_token) {$url .= "?token=" . $this->auth_token;}
        if ($method == 'GET') {
            $url .= '&' . http_build_query($data);
        } else {
            $data = json_encode($data);
        }
        // add my code
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '. base64_encode("$user:$pass")
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

        $error = curl_error($ch);
        curl_close($ch);
        $result = false;
        if (($code == 200 || $code == 201) && $response) {
            $result = json_decode($response);
            $error  = false;
        } else if ($code != 0) {
            if ($response) {
                $response    = json_decode($response, true);
                $error       = errorParser($response);
                $error = $error;
            } else {
                $error = 'ErrorCode: ' . $code;
            }
        }
        return $result;
    }

    function errorParser($array)
    {
        $result = '';
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($key !== 0) {
                    $result .= $key . ': ';
                }
                if (is_array($value)) {
                    $result .= errorParser($value) . "\n";
                } else {
                    $result .= $value;
                }
            }
        } else {
            $result = $array;
        }
        return $result;
    }
    
    $jayParsedAry = [
        "apiOperation" => "CREATE_CHECKOUT_SESSION", 
        "interaction" => [
              "operation" => "PURCHASE" 
           ], 
        "order" => [
                 "currency" => "EGP", 
                 "id" => "1102", 
                 "amount" => 50 
              ] 
     ]; 
     $res =  request("rest/version/56/merchant/TESTEGPTEST/session", $jayParsedAry, $user, $pass);
     print_r($res);

