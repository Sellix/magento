<?php
namespace Sellix\Pay\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $resource = '';
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }
    
    public function getTransactionByOrderId($order_id)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('sellixpay_order');
        $query = "select * from {$tableName} where order_id=".(int)($order_id);
        $result = $connection->fetchAll($query);
        if ($result) {
            foreach($result as $row) {
                return $row;
            }
        } else {
            return false;
        }
    }
    
    public function updateTransaction($order_id, $response='')
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
