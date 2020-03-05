<?php

namespace luya\payment\widgets;

use tpayLibs\src\_class_tpay\Notifications\BasicNotificationHandler;
use tpayLibs\src\_class_tpay\PaymentOptions\BasicPaymentOptions;
use tpayLibs\src\_class_tpay\Utilities\TException;
use tpayLibs\src\_class_tpay\Utilities\Util;
use tpayLibs\src\_class_tpay\Validators\PaymentTypes\PaymentTypeBasic;

class NotificationHandler extends BasicPaymentOptions
{
	public function __construct($merchantId, $merchantSecret, $trApiKey, $trApiPass)
	{
		$this->merchantSecret = $merchantSecret;
		$this->merchantId = $merchantId;
		$this->trApiKey = $trApiKey;
		$this->trApiPass = $trApiPass;
		parent::__construct();
	}

	public function getTpayNotification()
	{
		return $this->checkPayment();
	}

	/**
	 * Check cURL request from tpay server after payment.
	 * This method check server ip, required fields and md5 checksum sent by payment server.
	 * Display information to prevent sending repeated notifications.
	 * @param string $response Print response to Tpay server (enum: 'TRUE', 'FALSE').
	 * If empty, then you have to print it somewhere else to avoid rescheduling notifications
	 * @return array
	 * @throws TException
	 */
	private function checkPayment($response = 'TRUE')
	{
		Util::log('Basic payment notification', "POST params: \n" . print_r($_POST, true));
		$res = $this->getResponse(new PaymentTypeBasic());
		$this->setTransactionID($res['tr_id']);
		$checkMD5 = $this->isMd5Valid(
			$res['md5sum'],
			number_format($res['tr_amount'], 2, '.', ''),
			$res['tr_crc']
		);
		Util::logLine('Check MD5: ' . (int)$checkMD5);
		/*if ($this->validateServerIP === true && $this->isTpayServer() === false) {
			throw new TException('Request is not from secure server');
		}*/
		if ($checkMD5 === false) {
			throw new TException('MD5 checksum is invalid');
		}
		if (!empty($response) && is_string($response)) {
			echo $response;
		}

		return $res;
	}

	/**
	 * Check md5 sum to validate tpay response.
	 * The values of variables that md5 sum includes are available only for
	 * merchant and tpay system.
	 *
	 * @param string $md5sum md5 sum received from tpay
	 * @param float $transactionAmount transaction amount
	 * @param string $crc transaction crc
	 *
	 * @return bool
	 */
	private function isMd5Valid($md5sum, $transactionAmount, $crc)
	{
		if (!is_string($md5sum) || strlen($md5sum) !== 32) {
			return false;
		}

		return ($md5sum === md5($this->merchantId . $this->transactionID .
				$transactionAmount . $crc . $this->merchantSecret));
	}
}

