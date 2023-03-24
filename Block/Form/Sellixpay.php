<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sellix\Pay\Block\Form;

class Sellixpay extends \Magento\Payment\Block\Form
{
    /**
     * Sellix pay template
     *
     * @var string
     */
    protected $_template = 'Sellix_Pay::form/sellixpay.phtml';
    
    /**
     * @var \Sellix\Pay\Model\ConfigProvider $configProvider
     */
    protected $configProvider;
    
    /**
     * Constructor
     *
     * @param \Sellix\Pay\Model\ConfigProvider $configProvider
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Sellix\Pay\Model\ConfigProvider $configProvider,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        parent::__construct($context, $data);
    }
    
    /**
     * Get config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->configProvider->getConfig();
    }
}
