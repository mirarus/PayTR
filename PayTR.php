<?php

/**
 * PayTR Pos System
 * @author  Mirarus <aliguclutr@gmail.com>
 */
class PayTR
{

	private $config = [];
	private $customer = [];
	private $product = [];
	private $items = [];
	private $currency_codes = ['TL', 'USD', 'EUR', 'GBP'];
	private $lang_codes = ['tr', 'en'];
	private $currency_code;
	private $lang_code;


	public function setConfig($data=[])
	{
		if ($data['type'] == 'init') {
			if ($data['merchant_id'] == null || $data['merchant_key'] == null || $data['merchant_salt'] == null || $data['merchant_success_url'] == null || $data['merchant_failed_url'] == null) {
				exit("Missing api information.");
			} else{
				$this->config = [
					'merchant_id' => $data['merchant_id'],
					'merchant_key' => $data['merchant_key'],
					'merchant_salt' => $data['merchant_salt'],
					'merchant_success_url' => $data['merchant_success_url'],
					'merchant_failed_url' => $data['merchant_failed_url'],
					'max_installment' => (isset($data['max_installment']) ? $data['max_installment'] : 9),
					'debug_on' => (isset($data['debug_on']) ? $data['debug_on'] : false),
					'sandbox' => (isset($data['sandbox']) ? $data['sandbox'] : false),
					'installment' => (isset($data['installment']) ? $data['installment'] : false),
					'commission_rate' => (isset($data['commission_rate']) ? $data['commission_rate'] : 0)
				];
			}
		} if ($data['type'] == 'callback') {
			if ($data['merchant_key'] == null || $data['merchant_salt'] == null) {
				exit("Missing api information.");
			} else{
				$this->config = [
					'merchant_key' => $data['merchant_key'],
					'merchant_salt' => $data['merchant_salt']
				];
			}
		} 
	}

	public function setCustomer($data=[])
	{
		if ($data['name'] == null || $data['email'] == null || $data['phone'] == null || $data['address'] == null) {
			exit("Missing customer information.");
		} else{
			$this->customer = [
				'name' => $data['name'],
				'email' => $data['email'],
				'phone' => $data['phone'],
				'address' => $data['address']
			];
		}
	}
	
	public function setProduct($data=[])
	{
		if ($data['order_id'] == null || $data['amount'] == null) {
			exit("Missing product information.");
		} else{
			if ($data['amount'] >= 1) {
				$this->product = [
					'order_id' => $data['order_id'],
					'amount' => $data['amount']
				];
			} else{
				exit("Amount Should Be Minimum 1");
			}
		}
	}
	
	public function setItems($data=[])
	{
		if (!empty($data)) {
			$this->items = $data;
		}
	}

	public function setLocale($data=[])
	{
		if (in_array($data['currency'], $this->currency_codes)) {
			$this->currency_code = $data['currency'];
		} else{
			exit("Invalid Currency Code");
		}

		if (in_array($data['lang'], $this->lang_codes)) {
			$this->lang_code = $data['lang'];
		} else{
			exit("Invalid Lang Code");
		}
	}
	
	public function init()
	{
		$currency_code = (isset($this->currency_code) ? $this->currency_code : 'TL');
		$lang_code = (isset($this->lang_code) ? $this->lang_code : 'tr');
		$no_installment = (isset($this->config['installment']) ? 0 : 1);
		$sandbox = (isset($this->config['sandbox']) ? 1 : 0);
		$debug_on = (isset($this->config['debug_on']) ? 1 : 0);

		$max_installment = $this->config['max_installment'];

		$merchant_oid = uniqid() . 'PAYTR' . $this->product['order_id'];

		$payment_amount = number_format($this->product['amount'], 2, '.', '');
		$payment_amount = $payment_amount * 100;

		if (!empty($this->items)) {
			$user_basket = [];
			foreach ($this->items as $item) {
				$user_basket[] = [$item["name"], round($item["amount"], 2), (isset($item['piece']) ? $item['piece'] : 1)];
			}
			$basket = base64_encode(json_encode($user_basket));
		} else{
			$basket = null;
		}

		$hash_str = $this->config['merchant_id'] . $this->GetIP() . $merchant_oid . $this->customer['email'] . $payment_amount . $basket . $no_installment . $max_installment . $currency_code . $sandbox;
		$paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $this->config['merchant_salt'], $this->config['merchant_key'], true));

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => "https://www.paytr.com/odeme/api/get-token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => [
				'merchant_id' => $this->config['merchant_id'],
				'user_ip' => $this->GetIP(),
				'merchant_oid' => $merchant_oid,
				'email' => $this->customer['email'],
				'payment_amount' => $payment_amount,
				'paytr_token' => $paytr_token,
				'user_basket' => $basket,
				'debug_on' => $debug_on,
				'no_installment' => $no_installment,
				'max_installment' => $max_installment,
				'user_name' => $this->customer['name'],
				'user_phone' => $this->customer['phone'],
				'user_address' => $this->customer['address'],
				'merchant_ok_url' => $this->config['merchant_success_url'],
				'merchant_fail_url' => $this->config['merchant_failed_url'],
				'timeout_limit' => 30,
				'currency' => $currency_code,
				'test_mode' => $sandbox,
				'lang' => $lang_code
			]
		]);
		$response = @curl_exec($curl);
		if (curl_errno($curl)) {
			exit('PayTR Iframe Error! <br> Error: ' . curl_error($curl));
		} else{
			$result = json_decode($response, true);
			if ($result['status'] != 'success') {
				exit('PayTR Iframe failed! <br> Error: ' . $result['reason']);
			} else{
				return $result['token'];
			}
		}
		curl_close($curl);
	}

	public function callback()
	{
		$merchant_oid = $_POST['merchant_oid'];
		$status = $_POST['status'];
		$total_amount = $_POST['total_amount'];
		$Phash = $_POST['hash'];
		$order_id = explode('PAYTR', $merchant_oid);

		$hash = base64_encode(hash_hmac('sha256', $merchant_oid . $this->config['merchant_salt'] . $status . $total_amount, $this->config['merchant_key'], true));

		if ($hash != $Phash) {
			exit('PayTR notification failed: bad hash');
		} else{
			return [
				'merchant_oid' => $merchant_oid,
				'status' => $status,
				'total_amount' => explode(00, $total_amount)[0],
				'hash' => $Phash,
				'order_id' => $order_id[1]
			];
		}
	}

	public function GetIP()
	{
		if (getenv("HTTP_CLIENT_IP")) {
			$ip = getenv("HTTP_CLIENT_IP");
		} elseif (getenv("HTTP_X_FORWARDED_FOR")) {
			$ip = getenv("HTTP_X_FORWARDED_FOR");
			if (strstr($ip, ',')) {
				$tmp = explode (',', $ip);
				$ip = trim($tmp[0]);
			}
		} else{
			$ip = getenv("REMOTE_ADDR");
		}
		return $ip;
	}
}