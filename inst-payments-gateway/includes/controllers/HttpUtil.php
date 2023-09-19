<?php


class HttpUtil
{

    public static function post($url, $requestPath, $reqObject, $signature, $apikey, float $timStamp){

        $SIGN_SEPARATOR = ":";
        $sign = SHA256Util::sign($signature);

        $authorizationStr = $apikey
            . $SIGN_SEPARATOR
            . $timStamp
            . $SIGN_SEPARATOR
            . $sign;
        $curl = curl_init($url . $requestPath);
        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($curl, CURLOPT_POST, true);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, json_encode($reqObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) );
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $headers = array(
            "Content-Type: application/json; charset=utf-8",
            "Accept: application/json",
            "Authorization:" . $authorizationStr,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $responseText = curl_exec($curl);
        if (!$responseText) {
            echo('CURL_ERROR: ' . var_export(curl_error($curl)));
        }
        curl_close($curl);

        return $responseText;
    }

    public static function simplePost($url, $requestPath, $reqObject, $signature, $apikey, float $timStamp){
        $SIGN_SEPARATOR = ":";
        $sign = SHA256Util::sign($signature);

        $authorizationStr = $apikey
            . $SIGN_SEPARATOR
            . $timStamp
            . $SIGN_SEPARATOR
            . $sign;

        $curl = curl_init($url . $requestPath);
        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($curl, CURLOPT_POST, true);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, json_encode($reqObject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) );
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $headers = array(
            "Content-Type: application/json; charset=utf-8",
            "Accept: application/json",
            "Authorization:" . $authorizationStr,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $responseText = curl_exec($curl);
        if (!$responseText) {
            echo('CURL_ERROR: ' . var_export(curl_error($curl)));
        }
        curl_close($curl);

        return $responseText;
    }

}
