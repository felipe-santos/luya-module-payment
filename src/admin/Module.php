<?php

namespace luya\payment\admin;

use luya\payment\integrators\DatabaseIntegrator;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Payment Admin Module
 *
 * @property \luya\payment\base\Transaction $transaction
 *
 * @author Basil Suter <basil@nadar.io>
 * @since 1.0.0
 */
class Module extends \luya\admin\base\Module
{
    public $apis = [
        'api-payment-process' => 'luya\payment\admin\apis\ProcessController',
        'api-payment-processtrace' => 'luya\payment\admin\apis\ProcessTraceController',
        'api-payment-processitem' => 'luya\payment\admin\apis\ProcessItemController',
    ];
    
    public function getMenu()
    {
        return (new \luya\admin\components\AdminMenuBuilder($this))
            ->node('Payment', 'payment')
                ->group('Data')
                    ->itemApi('Payment', 'paymentadmin/process/index', 'label', 'api-payment-process')
                    ->itemApi('Article', 'paymentadmin/process-item/index', 'label', 'api-payment-processitem')
                    ->itemApi('Log', 'paymentadmin/process-trace/index', 'label', 'api-payment-processtrace');
    }

	/**
	 * @inheritDoc
	 * @throws InvalidConfigException
	 */
	public function init()
	{
		parent::init();

		if ($this->transaction === null) {
			throw new InvalidConfigException("The transaction property can not be null.");
		}
	}

	private $_transaction;

	/**
	 * Get the transaction object
	 *
	 * @return \luya\payment\base\Transaction
	 */
	public function getTransaction()
	{
		return $this->_transaction;
	}

    /**
     *
     * @throws InvalidConfigException
     * @var array A transaction object config array for the given provider:
     * + paypal:
     * ```php
     * 'class' => payment\transactions\PayPalTransaction::className(),
     * 'clientId' => 'ClientIdFromPayPalApplication',
     * 'clientSecret' => 'ClientSecretFromPayPalApplication',
     * 'mode' => 'live', // 'sandbox',
     * 'productDescription' => 'MyOnlineStore Order',
     * ```
     * + saferpay:
     * ```php
     * 'class' => payment\transactions\SaferPayTransaction::className(),
     * 'accountId' => 'SAFERPAYACCOUNTID', // each transaction can have specific attributes, saferpay requires an accountId',
     * ```
     * + stripe
     */
	public function setTransaction(array $config)
	{
		$this->_transaction = Yii::createObject($config);
	}

	private $_integrator;

	/**
	 * Setter method for integrator.
	 *
	 * @param array $config The configuration to use when the integrato is created (on get).
	 */
	public function setIntegrator(array $config)
	{
		$this->_integrator = $config;
	}

	/**
	 * Getter method for integrator.
	 *
	 * If there is no integrator defined trough setter method, the datbase integrator is used by default.
	 *
	 * @return \luya\payment\base\IntegratorInterface
	 */
	public function getIntegrator()
	{
		// use default database integrator config if not defined.
		if ($this->_integrator === null) {
			$this->_integrator = ['class' => DatabaseIntegrator::class];
		}

		// create the integrator object if not done previously.
		if (is_array($this->_integrator)) {
			$this->_integrator = Yii::createObject($this->_integrator);
		}

		return $this->_integrator;
	}
	//todo translations
}
