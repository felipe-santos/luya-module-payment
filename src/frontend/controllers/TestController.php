<?php

namespace luya\payment\frontend\controllers;

use app\helpers\CorsCustom;
use luya\admin\models\Config;
use luya\order\models\Item;
use Yii;
use luya\payment\Pay;
use yii\filters\HttpCache;
use luya\payment\PaymentException;

class TestController extends \luya\web\Controller
{
    /**
     * Disable cache
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => CorsCustom::class
        ];

        $behaviors[] = [
            'class' => HttpCache::class,
            'cacheControlHeader' => 'no-store, no-cache',
            'lastModified' => function ($action, $params) {
                return time();
            },
        ];

        return $behaviors;
    }

    public function beforeAction($action)
    {
	    $this->enableCsrfValidation = false;
	    if (parent::beforeAction($action)) {
            if (YII_ENV_DEV && YII_DEBUG) {
                return true;
            }
        }
	    return false;

    }

    /**
     * @return \yii\web\Response
     * @throws PaymentException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex() //todo test payment
    {
        if(isset(Yii::$app->request->post()["orderId"])) {
            $order = Item::findOne(Yii::$app->request->post()["orderId"]);
            $pay = new Pay();
            $pay->setOrderId($order->id);
            $pay->setCurrency('PLN');
            $pay->setSuccessLink(['success', 'orderId' => $order->id]);
            $pay->setErrorLink(['error']);
            $pay->setAbortLink(['abort']);

            foreach ($order->articles as $article) {
                $pay->addItem($article->article->name, $article->amount, $article->price);
            }
            if($order->shipping->price > 0) {
                $pay->addShipping($order->shipping->api->name, $order->shipping->price);
            }
            $pay->setTotalAmount($order->total_price);

            return $pay->dispatch($this);
        }

        throw new PaymentException("Error, invalid payment process.");
    }
    
    public function actionSuccess($orderId)
    {
        $order = Item::findOne($orderId);
        if (!Pay::isSuccess($order->lastPayment->id)) {
            throw new \Exception("The request url is invalid, the payment process was not closed successfull.");
        }

        return $this->redirect(Config::get('app_url') . '/account/order-history');
    }
    
    public function actionError($orderId)
    {
        return $this->redirect(Config::get('app_url') . '/account/order-history');
    }
    
    public function actionAbort($orderId)
    {
        return $this->redirect(Config::get('app_url') . '/account/order-history');
    }
}
