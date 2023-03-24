<?php
namespace Sellix\Pay\Controller\Index;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
     * @var \Magento\Framework\Filesystem\Driver\File $fileDriver
     */
    protected $fileDriver;

    /**
     * Constructor
     *
     * @param \Sellix\Pay\Helper\Data $helper
     * @param \Sellix\Pay\Model\Pay $payment
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Sellix\Pay\Helper\Data $helper,
        \Sellix\Pay\Model\Pay $payment,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
        $this->fileDriver = $fileDriver;
        parent::__construct($context);
    }
    
    /**
     * CreateCsrfValidationException
     *
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return void
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
    
    /**
     * ValidateForCsrf
     *
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return void
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Executor
     *
     * @return void
     */
    public function execute()
    {
        $data = json_decode($this->fileDriver->fileGetContents('php://input'), true);
        $model = $this->payment;

        try {
            $model->log('Webhook received data:');
            $model->log($data);
            
            if ((null === $data['data']) || (null === $data['data']['uniqid']) || empty($data['data']['uniqid'])) {
                $message = sprintf(__('Sellixpay: suspected fraud. Code-001'));
                throw new \Magento\Framework\Exception\LocalizedException($message);
            }
            
            $sellix_order = $this->validSellixOrder($model, $data['data']['uniqid']);

            $params = $this->getRequest()->getParams();
            if (isset($params['order_id']) && !empty($params['order_id'])) {
                $orderId = trim($params['order_id']);
                $order = $this->orderFactory->create()->load($orderId);
                
                $model->log(__('Concerning Magento order:').$order->getId());
                
                $message1 = 'Order #' . $order->getId();
                $message2 = ' (' . $sellix_order['uniqid'] . '). Status: ' . $sellix_order['status'];
                
                $model->log($message1.$message2);
                
                if ($sellix_order['status'] == 'PROCESSING') {
                    $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $order->setState($state);
                    $order->setStatus($status);
                    $order->setTotalPaid($order->getGrandTotal());
                    
                    $comment1 = __('Sellix payment processing. ');
                    $comment2 = __('Transaction ID: '). $sellix_order['uniqid'];
                    $comment3 = __(', Status: '). $sellix_order['status'];
                    $comment = $comment1.$comment2.$comment3;
                    $order->addStatusHistoryComment($comment, $status);
                    $order->save();
                } elseif ($sellix_order['status'] == 'COMPLETED') {
                    $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $order->setState($state);
                    $order->setStatus($status);
                    $order->setTotalPaid($order->getGrandTotal());
                    
                    $comment1 = __('Sellix payment successful. ');
                    $comment2 = __('Transaction ID: '). $sellix_order['uniqid'];
                    $comment3 = __(', Status: '). $sellix_order['status'];
                    $comment = $comment1.$comment2.$comment3;
                    $order->addStatusHistoryComment($comment, $status);
                    $order->save();
                } elseif ($sellix_order['status'] == 'WAITING_FOR_CONFIRMATIONS') {
                    $comment1 = __('Awaiting crypto currency confirmations. ');
                    $comment2 = __('Transaction ID: '). $sellix_order['uniqid'];
                    $comment3 = __(', Status: '). $sellix_order['status'];
                    $comment = $comment1.$comment2.$comment3;
                    $order->hold();
                    $order->addStatusHistoryComment($comment, \Magento\Sales\Model\Order::STATE_HOLDED);
                    $order->save();
                } elseif ($sellix_order['status'] == 'PARTIAL') {
                    $comment1 = __('Cryptocurrency payment only partially paid. ');
                    $comment2 = __('Transaction ID: '). $sellix_order['uniqid'];
                    $comment3 = __(', Status: '). $sellix_order['status'];
                    $comment = $comment1.$comment2.$comment3;
                    $order->hold();
                    $order->addStatusHistoryComment($comment, \Magento\Sales\Model\Order::STATE_HOLDED);
                    $order->save();
                } elseif ($sellix_order['status'] == 'PENDING') {
                    $comment1 = __('Payment request is created. ');
                    $comment2 = __('Transaction ID: '). $sellix_order['uniqid'];
                    $comment3 = __(', Status: '). $sellix_order['status'];
                    $comment = $comment1.$comment2.$comment3;
                    $order->addStatusHistoryComment($comment);
                    $order->save();
                } else {
                    $comment1 = __('Order canceled. ');
                    $comment2 = __('Transaction ID: '). $sellix_order['uniqid'];
                    $comment3 = __(', Status: '). $sellix_order['status'];
                    $comment = $comment1.$comment2.$comment3;
                    $order->cancel();
                    $order->addStatusHistoryComment($comment, \Magento\Sales\Model\Order::STATE_CANCELED);
                    $order->save();
                }
                $this->helper->updateTransaction($order->getId(), json_encode($sellix_order));
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Empty response received from gateway.'));
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
        $this->getResponse()->setBody('Web hook finished');
    }
    
    /**
     * Generate Valid Sellix Order
     *
     * @param \Sellix\Pay\Model\Pay $model
     * @param string $order_uniqid
     *
     * @return array sellix order
     */
    public function validSellixOrder($model, $order_uniqid)
    {
        $route = "/v1/orders/" . $order_uniqid;
        $response = $model->sellixPostAuthenticatedJsonRequest($route, '', '', 'GET');

        $model->log(__('Order validation returned:'.$response['body']));
        
        if (isset($response['body']) && !empty($response['body'])) {
            $responseDecode = json_decode($response['body'], true);
            if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
                $message = __('Payment error: '.$responseDecode['status'].'-'.$responseDecode['error']);
                throw new \Magento\Framework\Exception\LocalizedException($message);
            }

            return $responseDecode['data']['order'];
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unable to verify order via Sellix Pay API'));
        }
    }
}
