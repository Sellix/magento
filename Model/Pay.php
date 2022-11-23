<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sellix\Pay\Model;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;

class Pay extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'sellixpay';
    
    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUsePay = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = true;
    
    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     *
     * @var Magento\Checkout\Model\Session
     */
    protected $orderSession;
    
    /**
     *
     * @var Sellix\Pay\Helper\Data
     */
    protected $sellixpayHelper;
    
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    
    protected $directory_list;
    
    public function __construct(
        \Sellix\Pay\Helper\Data $sellixpayHelper,
        \Magento\Checkout\Model\Session $orderSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list, 
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Directory\Helper\Data $directory = null
    ) {
        $this->sellixpayHelper = $sellixpayHelper;
        $this->orderSession = $orderSession;
        $this->orderFactory = $orderFactory;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->directory_list = $directory_list;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return $this->getConfigValue('instructions');
    }
    
    public function getConfigValue($key)
    {
        $pathConfig = 'payment/' . $this->_code . "/" . $key;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue($pathConfig, $storeScope);
    }
    
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        if ($isAvailable) {
            $api_key = $this->getConfigValue('api_key', $quote ? $quote->getStoreId() : null);
            $email = $this->getConfigValue('email', $quote ? $quote->getStoreId() : null);
            
            if (empty($api_key) || empty($email)) {
                $isAvailable = false;
            }
        }
        return $isAvailable;
    }
    
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }
    
    public function isInitializeNeeded()
    {
        return true;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }
    
    public function getCheckout()
    {
        return $this->orderSession;
    }
    
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    public function getOrder()
    {
        return $this->getCheckout()->getLastRealOrder();
    }

    public function getCheckoutRedirectUrl($params = []) 
    {
        return $this->urlBuilder->getUrl('sellixpay/index/pay', $params);
    }

    public function getWebhookUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/webhook', $params);
    }
    
    public function getCallbackUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/callback', $params);
    }
    
    public function getCancelUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/cancel', $params);
    }
    
    public function getCheckoutSuccessUrl()
    {
        return $this->urlBuilder->getUrl('checkout/onepage/success');
    }
    
    public function getStoreUrl()
    {
        return $this->urlBuilder->getUrl();
    }
    
    public function getStoreName()
    {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();        
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeName = $storeManager->getStore()->getName();
        if (empty($storeName)) {
            $storeName = $storeManager->getStore()->getStoreUrl();
        }
        return $storeName;
    }
    
    public function log($message)
    {
        $debug = $this->getConfigValue('debug');
        if ($debug) {
            file_put_contents($this->directory_list->getPath('log') . '/sellixpay.log', date("Y-m-d H:i:s").": ", FILE_APPEND);
            file_put_contents($this->directory_list->getPath('log') . '/sellixpay.log', print_r($message, true), FILE_APPEND);
            file_put_contents($this->directory_list->getPath('log') . '/sellixpay.log', "\n", FILE_APPEND);
        }
    }
    
    public function getApiUrl()
    {
        return 'https://dev.sellix.io';
    }
    
    function sellixPostAuthenticatedJsonRequest($route, $body = false, $extra_headers = false, $method="POST")
    {
        $server = $this->getApiUrl();

        $url = $server . $route;

        $uaString = 'Sellix Magento 2 (PHP ' . PHP_VERSION . ')';
        $apiKey = trim($this->getConfigValue('api_key'));
        $headers = array(
            'Content-Type: application/json',
            'User-Agent: '.$uaString,
            'Authorization: Bearer ' . $apiKey
        );

        if($extra_headers && is_array($extra_headers)) {
            $headers = array_merge($headers, $extra_headers);
        }
        
        $this->log($url);
        $this->log($headers);
        $this->log($body);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (! empty( $body )) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $body ));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response['body'] = curl_exec($ch);
        $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->log($response['body']);
        $response['error'] = curl_error($ch);
        
        return $response;
    }
}
