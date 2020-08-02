<?php

namespace luya\payment\transactions;

use luya\order\models\Item;
use luya\payment\providers\TPayProvider;
use luya\payment\widgets\NotificationHandler;
use tpayLibs\examples\TransactionNotification;
use Yii;
use luya\payment\base\Transaction;
use luya\payment\PaymentException;
use luya\payment\providers\PayPalProvider;
use yii\base\InvalidConfigException;

/**
 * TPay Transaction.
 *
 * @author Tomasz Fijałkowski <biuro@vave.pl>
 * @since 1.0.0
 */
class TPayTransaction extends Transaction
{
    /**
     * @var int The merchant id
     */
    public $merchantId;
    
    /**
     * @var string the merchant secret.
     */
    public $merchantSecret;
    
    /**
     * @var string
     */
    public $api_key;

	/**
	 * @var string
	 */
    public $api_password;

	/**
	 * {@inheritDoc}
	 * @throws InvalidConfigException
	 */
    public function init()
    {
        parent::init();
        
        if ($this->merchantId === null || $this->merchantSecret === null || $this->api_key === null || $this->api_password === null) {
            throw new InvalidConfigException("the tpay properite can not be null!");
        }
    }
    
    /**
     * Get the TPay Provider
     *
     * @return TPayProvider
     */
    public function getProvider()
    {
        return new TPayProvider();
    }

	private function getOrderDescription()
	{
		if (empty($this->productDescription)) {
			return 'Zamówienie: ' . $this->getModel()->getOrderId();
		}

		return $this->productDescription;
	}
    
    /**
     * As all amounts are provided in cents we have to calculate them to not cents
     *
     * @param unknown $amount
     */
    public static function floatAmount($value)
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * {@inheritDoc}
     */
    public function create()
    {
    	$crc = $this->getModel()->getOrderId();
	    /*$md5sum = md5($this->merchantId . $this->getModel()->getTotalAmount() . $crc . $this->merchantSecret);

	    return 'https://secure.tpay.com?id='. $this->merchantId .'&amount='. $this->getModel()->getTotalAmount() . '&description=test&crc='.$this->getModel()->getOrderId().'&md5sum=' . $md5sum . '&name=Tomasz+F&email=biuro@vave.pl';*/
	    Yii::$app->session->remove('storeTransactionId');
        $order = Item::findOne($this->getModel()->getOrderId());
	    $url = $this->getProvider()->call('create', [
	        "merchantId" => $this->merchantId,
	        "merchantSecret" => $this->merchantSecret,
	        "api_key" => $this->api_key,
	        "api_password" => $this->api_password,
			"amount" => $this->getModel()->getTotalAmount(),
			"description" => $this->getOrderDescription(),
			"crc" => $this->getModel()->getOrderId(),
	        "group" => 150,
	        //"group" => Yii::$app->request->get('group', false),
	        "return_url" => $this->getModel()->getTransactionGatewayBackLink(),
	        "return_error_url" => $this->getModel()->getTransactionGatewayFailLink(),
		    "result_url" => $this->getModel()->getTransactionGatewayNotifyLink(),
			"language" => "pl",
	        //"language" => Yii::$app->request->get('language', false),
	        "email" => $order->customer->email,
			"name" => $order->customer->firstname,
	        //"email" => Yii::$app->request->get('email', false),
	        //"name" => Yii::$app->request->get('name', false),
	        "accept_tos" => 1
        ]);

	    return json_encode($url);
    }
    
    /**
     * {@inheritDoc}
     */
    public function back()
    {
        return $this->getContext()->redirect('localhost:4200');
        //todo back action
		$transactionId = Yii::$app->session->get('storeTransactionId');
	    var_dump($this->getModel()->getOrderId());
	    /*$response = $this->getProvider()->call('execute', [
		    "merchantId" => $this->merchantId,
		    "merchantSecret" => $this->merchantSecret,
		    "api_key" => $this->api_key,
		    "api_password" => $this->api_password,
		    "trId" => Yii::$app->request->get('trId')
	    ]);*/
    }
    
    /**
     * {@inheritDoc}
     */
    public function notify()
    {
	    $notification = (new NotificationHandler($this->merchantId, $this->merchantSecret, $this->api_key, $this->api_password))->getTpayNotification();

	    if ($notification['tr_status'] == 'TRUE') {
		    return $this->redirectApplicationSuccess();
	    }

	    return $this->redirectApplicationError();
    }
    
    /**
     * {@inheritDoc}
     */
    public function fail()
    {
        return $this->redirectApplicationError();
    }
    
    /**
     * {@inheritDoc}
     */
    public function abort()
    {
        return $this->redirectApplicationAbort();
    }
}
