<?php
namespace Sellix\Pay\Helper;

use Sellix\Pay\Model\OrderFactory as SellixPayOrder;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\ResourceConnection $resource
     */
    public $resource = '';
    
    /**
     * @var Sellix\Pay\Model\OrderFactory $sellixpayOrder
     */
    protected $sellixpayOrder;
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param SellixPayOrder $sellixpayOrder
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        SellixPayOrder $sellixpayOrder
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->sellixpayOrder = $sellixpayOrder;
    }
    
    /**
     * Get Transaction By Order Id
     *
     * @param string $order_id
     */
    public function getTransactionByOrderId($order_id)
    {
        $model = $this->sellixpayOrder->create();
        $model = $model->load($order_id, 'order_id');
        
        if ($model && $model->getId() > 0) {
            $row = [
                'id' => $model->getId(),
                'order_id' => $model->getOrderId(),
                'response' => $model->getResponse(),
            ];
            return $row;
        } else {
            return false;
        }
    }
    
    /**
     * Update Transaction
     *
     * @param string $order_id
     * @param string $response
     */
    public function updateTransaction($order_id, $response = '')
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('sellixpay_order');
        
        $transaction = $this->getTransactionByOrderId($order_id);
        if ($transaction) {
            $connection->update(
                $tableName,
                ['response' => $response],
                "order_id=".$order_id
            );
        } else {
            $connection->insert(
                $tableName,
                ['response' => $response, 'order_id' => $order_id]
            );
        }
    }
}
