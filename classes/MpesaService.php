<?php

class MpesaService
{
    private $baseUrl;

    public function __construct()
    {
        $mode = strtolower(trim(app_env('MPESA_ENV', 'production')));
        $this->baseUrl = $mode === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    private function setting($key)
    {
        return trim((string) app_env($key, ''));
    }

    private function request($url, array $headers, $body = null)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for M-PESA payments.');
        }
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        if ($response === false || $error !== '') {
            throw new RuntimeException('M-PESA network request failed.');
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || $status < 200 || $status >= 300) {
            throw new RuntimeException('M-PESA provider returned an invalid response.');
        }
        return $decoded;
    }

    public function initiateStkPush($phone, $amount, $accountReference, $description)
    {
        $key = $this->setting('MPESA_CONSUMER_KEY');
        $secret = $this->setting('MPESA_CONSUMER_SECRET');
        $shortcode = $this->setting('MPESA_SHORTCODE');
        $partyB = $this->setting('MPESA_PARTY_B');
        $passkey = $this->setting('MPESA_PASSKEY');
        $callback = $this->setting('MPESA_CALLBACK_URL');
        if ($key === '' || $secret === '' || $shortcode === '' || $partyB === '' || $passkey === '' || $callback === '') {
            throw new RuntimeException('M-PESA production configuration is incomplete.');
        }
        if (strpos($callback, 'https://') !== 0 && strtolower(app_env('MPESA_ENV', 'production')) === 'production') {
            throw new RuntimeException('The production M-PESA callback URL must use HTTPS.');
        }
        $token = base64_encode($key . ':' . $secret);
        $auth = $this->request($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials', array(
            'Authorization: Basic ' . $token,
            'Content-Type: application/json',
        ));
        if (empty($auth['access_token'])) {
            throw new RuntimeException('M-PESA access token was not returned.');
        }
        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        $response = $this->request($this->baseUrl . '/mpesa/stkpush/v1/processrequest', array(
            'Authorization: Bearer ' . $auth['access_token'],
            'Content-Type: application/json',
        ), json_encode(array(
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->setting('MPESA_TRANSACTION_TYPE') ?: 'CustomerPayBillOnline',
            'Amount' => (int) round((float) $amount),
            'PartyA' => $phone,
            'PartyB' => $partyB,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callback,
            'AccountReference' => $this->setting('MPESA_ACCOUNT_REFERENCE') ?: $accountReference,
            'TransactionDesc' => $this->setting('MPESA_TRANSACTION_DESC') ?: $description,
        )));
        if (empty($response['CheckoutRequestID'])) {
            throw new RuntimeException(isset($response['errorMessage']) ? $response['errorMessage'] : 'M-PESA STK request was rejected.');
        }
        return $response;
    }

    public static function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', trim((string) $phone));
        if (strpos($phone, '+') === 0) $phone = substr($phone, 1);
        if (preg_match('/^0([17][0-9]{8})$/', $phone, $match)) $phone = '254' . $match[1];
        if (!preg_match('/^254[17][0-9]{8}$/', $phone)) return false;
        return $phone;
    }
}
