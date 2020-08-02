<?php

namespace luya\payment\widgets;

use tpayLibs\src\_class_tpay\TransactionApi;
use tpayLibs\src\_class_tpay\Utilities\TException;
use Yii;

class TransactionApiExample extends TransactionApi
{
	private $trId;
	public function __construct($merchantId, $merchantSecret, $trApiKey, $trApiPass)
	{
		$this->merchantSecret = $merchantSecret;
		$this->merchantId = $merchantId;
		$this->trApiKey = $trApiKey;
		$this->trApiPass = $trApiPass;
		parent::__construct();
	}
	public function getTransaction($transactionId)
	{
		$this->trId = $transactionId;
		try {
			$transaction = $this->setTransactionID($transactionId)->get();
			print_r($transaction);
		} catch (TException $e) {
			var_dump($e);
		}
	}

	public function createTransaction($config)
	{
		/**
		 * Create new transaction
		 */
		try {
			$res = $this->create($config);
			$this->trId = $res['title'];
			Yii::$app->session->set('storeTransactionId', $this->trId);
			return ["transactionId" => $this->trId];
		} catch (TException $e) {
			throw new TException($e);
		}
	}
}

