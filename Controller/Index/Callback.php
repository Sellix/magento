<?php
namespace Sellix\Pay\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Callback extends \Magento\Framework\App\Action\Action
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
     * Constructor
     *
     * @param \Sellix\Pay\Helper\Data $helper
     * @param \Sellix\Pay\Model\Pay $payment
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Sellix\Pay\Helper\Data $helper,
        \Sellix\Pay\Model\Pay $payment,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }
    
    /**
     * Executor
     *
     * @return void
     */
    public function execute()
    {
        $model = $this->payment;
        $session = $this->checkoutSession;
        try {
            $params = $this->getRequest()->getParams();
            if (isset($params['order_id']) && !empty($params['order_id'])) {
                $orderId = trim($params['order_id']);
                $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                return $this->getResponse()->setRedirect($model->getCheckoutSuccessUrl());
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Empty response received from gateway.'));
            }
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            $model->log('Return from Gateway Catch');
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
}
