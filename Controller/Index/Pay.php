<?php
namespace Sellix\Pay\Controller\Index;

class Pay extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Sellix\Pay\Helper\Data $helper
     */
    protected $helper;
    
    /**
     * @var \Sellix\Pay\Model\Pay $payment
     */
    protected $payment;
    
    /**
     * @var \Magento\Sales\Model\OrderFactory $orderFactory
     */
    protected $orderFactory;
    
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;
    
    /**
     * @var \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory
     */
    protected $resultRedirectFactory;
    
    /**
     * Constructor
     *
     * @param \Sellix\Pay\Helper\Data $helper
     * @param \Sellix\Pay\Model\Pay $payment
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Sellix\Pay\Helper\Data $helper,
        \Sellix\Pay\Model\Pay $payment,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Executor
     *
     * @return void
     */
    public function execute()
    {
        try {
            $model = $this->payment;
            $session = $this->checkoutSession;
            
            $checkoutSession = $model->getCheckout();

            $order = $model->getOrder();
            if ($order && $order->getId() > 0) {
                $payment_url = $this->generateSellixPayment($model, $order);
                
                $model->log('Payment process concerning order '.$order->getId().' returned: '.$payment_url);
                
                $resultRedirect = $this->resultRedirectFactory->create();
                $redirectLink = $payment_url;
                $resultRedirect->setUrl($redirectLink);
                return $resultRedirect;
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
    
    /**
     * Generate Sellix Payment
     *
     * @param \Sellix\Pay\Model\Pay $model
     * @param \Magento\Sales\Model\OrderFactory $order
     *
     * @return string sellix checkout payment url
     */
    public function generateSellixPayment($model, $order)
    {
        $params = [
            'title' => $model->getConfigValue('order_id_prefix') . $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'return_url' => $model->getCallbackUrl(['order_id' => $order->getId()]),
            'webhook' => $model->getWebhookUrl(['order_id' => $order->getId()]),
            'email' => $order->getCustomerEmail(),
            'value' => number_format($order->getGrandTotal(), 2, '.', '')
        ];

        $route = "/v1/payments";
        $response = $model->sellixPostAuthenticatedJsonRequest($route, $params);

        if (isset($response['body']) && !empty($response['body'])) {
            $responseDecode = json_decode($response['body'], true);
            if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
                $error_message = __('Payment error: '.$responseDecode['status'].'-'.$responseDecode['error']);
                throw new \Magento\Framework\Exception\LocalizedException($error_message);
            }

            $url = $responseDecode['data']['url'];
            if ($model->getConfigValue('url_branded')) {
                if (isset($responseDecode['data']['url_branded'])) {
                    $url = $responseDecode['data']['url_branded'];
                }
            }

            return $url;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Payment error: '.$response['error']));
        }
    }
}
