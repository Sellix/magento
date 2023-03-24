<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sellix\Pay\Model;

use Magento\Framework\Model\AbstractModel;

class Order extends AbstractModel
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init(\Sellix\Pay\Model\ResourceModel\Order::class);
    }
}
