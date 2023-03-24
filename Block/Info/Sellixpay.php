<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sellix\Pay\Block\Info;

class Sellixpay extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Sellix_Pay::info/sellixpay.phtml';
    
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;
    
    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }
    
    /**
     * Get sellix gateway text from checkout session
     *
     * @return string
     */
    public function getSellixpayGateway()
    {
        $payment_gateway = $this->checkoutSession->getSellixPaymentGateway();
        return $payment_gateway;
    }
}
