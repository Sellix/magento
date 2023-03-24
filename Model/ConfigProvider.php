<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sellix\Pay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Asset\Repository;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        'sellixpay'
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;
    
    /**
     * @var \Magento\Framework\View\Asset\Repository $assetRepo
     */
    protected $assetRepo;

    /**
     * Constructor
     *
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        Repository $assetRepo
    ) {
        $this->escaper = $escaper;
        $this->assetRepo = $assetRepo;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * Get config
     *
     * @return string[] $config
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment'][$code]['redirectUrl'] = $this->methods[$code]->getCheckoutRedirectUrl();
            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     *
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }
}
