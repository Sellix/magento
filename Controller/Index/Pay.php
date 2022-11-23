<?php
namespace Sellix\Pay\Controller\Index;

class Pay extends \Magento\Framework\App\Action\Action
{
    protected $helper;
    protected $payment;
    protected $orderFactory;

    public function __construct(
        \Sellix\Pay\Helper\Data $helper,
        \Sellix\Pay\Model\Pay $payment,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $model = $this->_objectManager->get('Sellix\Pay\Model\Pay');
            $session = $this->_objectManager->get('Magento\Checkout\Model\Session');
            
            $checkoutSession = $model->getCheckout();
            $payment_gateway = $checkoutSession->getSellixPaymentGateway();
            
            $order = $model->getOrder();
            if ($order && $order->getId() > 0) {
                $payment_url = $this->generateSellixPayment($model, $order, $payment_gateway);
                
                $model->log('Payment process concerning order '.$order->getId().' returned: '.$payment_url);
                
                echo '<script>window.top.location.href = "'.$payment_url.'";</script>';
                exit;
            } else {
                $this->_redirect('checkout/cart');
            }
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            $model->log('Payment Gateway Request Catch');
            $model->log('Exception:'.$e->getMessage());
            $message = __('Payment is failed. '.$error_message);
            if (isset($order) && $order && $order->getId() > 0) {
                $order->cancel();
                $order->addStatusHistoryComment($message, \Magento\Sales\Model\Order::STATE_CANCELED);
                $order->save();
                $session->restoreQuote();
            }
            $this->messageManager->addError($message);
            $this->_redirect('checkout/cart');
        }
    }
    
    public function generateSellixPayment($model, $order, $payment_gateway)
    {
        if (!empty($payment_gateway)) {
            $params = [
                'title' => $model->getConfigValue('order_id_prefix') . $order->getIncrementId(),
                'currency' => $order->getOrderCurrencyCode(),
                'return_url' => $model->getCallbackUrl(['order_id' => $order->getId()]),
                'webhook' => $model->getWebhookUrl(['order_id' => $order->getId()]),
                'email' => $order->getCustomerEmail(),
                'value' => number_format($order->getGrandTotal(), 2, '.', ''),
                'gateway' => $payment_gateway,
                'confirmations' => $model->getConfigValue('confirmations')
            ];

            $route = "/v1/payments";
            $response = $model->sellixPostAuthenticatedJsonRequest($route, $params);
            
            if (isset($response['body']) && !empty($response['body'])) {
                $responseDecode = json_decode($response['body'], true);
                if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
                    throw new \Exception (__('Payment error: '.$responseDecode['status'].'-'.$responseDecode['error']));
                }
                
                return $responseDecode['data']['url'];
            } else {
                throw new \Exception (__('Payment error: '.$response['error']));
            }
        } else{
            throw new \Exception(__('Payment Gateway Error: Sellix Before API Error: Payment Method Not Selected'));
        }
    }
}
