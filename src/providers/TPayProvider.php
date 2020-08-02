<?php

namespace luya\payment\providers;

use Exception;
use luya\payment\widgets\TransactionApiExample;
use Yii;
use luya\payment\base\Provider;
use Stripe\PaymentIntent;

/**
 * Tpay Provider
 *
 * @author Tomasz Fijałkowski <biur@vave.pl>
 * @since 1.0.0
 */
class TPayProvider extends Provider
{
    public function getId()
    {
        return 'tpay';
    }



	public function callCreate($merchantId, $merchantSecret, $api_key, $api_password, $amount, $description, $crc, $group, $return_url, $return_error_url, $result_url, $language, $email, $name, $accept_tos)
	{
		$transaction = new TransactionApiExample($merchantId, $merchantSecret, $api_key, $api_password);
		$config = array(
			'amount' => $amount,
			'description' => $description,
			'crc' => $crc,
			'return_url' => $return_url,
			'return_error_url' => $return_error_url,
			'result_url' => $result_url,
			'email' => $email,
			'group' => $group,
			'name' => $name,
			'accept_tos' => $accept_tos
		);
		return $transaction->createTransaction($config);
	}

	public function callExecute($merchantId, $merchantSecret, $api_key, $api_password, $trId)
	{
		$transaction = (new TransactionApiExample($merchantId, $merchantSecret, $api_key, $api_password))->getTransaction($trId);

		return $transaction;
	}
}
