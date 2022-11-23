<?php
namespace Sellix\Pay\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Webhook extends \Magento\Framework\App\Action\Action
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
        $data = json_decode(file_get_contents('php://input'), true);
        $model = $this->_objectManager->get('Sellix\Pay\Model\Pay');
        $session = $this->_objectManager->get('Magento\Checkout\Model\Session');
        try {
            $model->log('Webhook received data:');
            $model->log($data);
            
            $sellix_order = $this->validSellixOrder($model, $data['data']['uniqid']);
            
            $model->log(__('Concerning Sellix order:'));
            $model->log($sellix_order);
            
            $params = $this->getRequest()->getParams();
            if (isset($params['order_id']) && !empty($params['order_id'])) {
                $orderId = trim($params['order_id']);
                $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                
                $model->log(__('Concerning Magento order:').$order->getId());
                
                $model->log('Order #' . $order->getId() . ' (' . $sellix_order['uniqid'] . '). Status: ' . $sellix_order['status']);
                
                if ($sellix_order['status'] == 'COMPLETED') {
                    $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $order->setState($state);
                    $order->setStatus($status);
                    $order->setTotalPaid($order->getGrandTotal());
                    $comment = __('Sellix payment successful. '). __('Transaction ID: '). $sellix_order['uniqid']. __(', Status: '). $sellix_order['status'];
                    $order->addStatusHistoryComment($comment, $status);
                    $order->save();

                    $this->helper->updateTransaction($order->getId(), json_encode($sellix_order));
                } elseif ($sellix_order['status'] == 'WAITING_FOR_CONFIRMATIONS') {
                    $comment = __('Awaiting crypto currency confirmations. '). __('Transaction ID: '). $sellix_order['uniqid']. __(', Status: '). $sellix_order['status'];
                    $order->hold();
                    $order->addStatusHistoryComment($comment, \Magento\Sales\Model\Order::STATE_HOLDED);
                    $order->save();
                } elseif ($sellix_order['status'] == 'PARTIAL') {
                    $comment = __('Cryptocurrency payment only partially paid. '). __('Transaction ID: '). $sellix_order['uniqid']. __(', Status: '). $sellix_order['status'];
                    $order->hold();
                    $order->addStatusHistoryComment($comment, \Magento\Sales\Model\Order::STATE_HOLDED);
                    $order->save();
                } else {
                    $comment = __('Order canceled. '). __('Transaction ID: '). $sellix_order['uniqid']. __(', Status: '). $sellix_order['status'];
                    $order->cancel();
                    $order->addStatusHistoryComment($comment, \Magento\Sales\Model\Order::STATE_CANCELED);
                    $order->save();
                }
            } else {
                throw new \Exception(__('Empty response received from gateway.'));
            }
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            $model->log('Webhook from Gateway Catch');
            $model->log('Exception:'.$e->getMessage());
            $message = __('Payment error. '.$error_message);
            if (isset($order) && $order && $order->getId() > 0) {
                $order->cancel();
                $order->addStatusHistoryComment($message, \Magento\Sales\Model\Order::STATE_CANCELED);
                $order->save();
            }
        }
    }
    
    public function validSellixOrder($model, $order_uniqid)
    {
        $route = "/v1/orders/" . $order_uniqid;
        $response = $model->sellixPostAuthenticatedJsonRequest($route,'','','GET');

        $model->log(__('Order validation returned:'.$response['body']));
        
        if (isset($response['body']) && !empty($response['body'])) {
            $responseDecode = json_decode($response['body'], true);
            if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
                throw new \Exception (__('Payment error: '.$responseDecode['status'].'-'.$responseDecode['error']));
            }

            return $responseDecode['data']['order'];
        } else {
            throw new \Exception (__('Unable to verify order via Sellix Pay API'));
        }
    }
}
