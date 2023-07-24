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
    
    /**
     * @var bool
     */
    protected $_isGateway = true;
    
    /**
     * @var bool
     */
    protected $_canAuthorize = false;
    
    /**
     * @var bool
     */
    protected $_canCapture = false;
    
    /**
     * @var bool
     */
    protected $_canCapturePartial = false;
    
    /**
     * @var bool
     */
    protected $_canRefund = false;
    
    /**
     * @var bool
     */
    protected $_canVoid = false;
    
    /**
     * @var bool
     */
    protected $_canUseInternal = false;
    
    /**
     * @var bool
     */
    protected $_canUsePay = true;
    
    /**
     * @var bool
     */
    protected $_canUseForMultishipping = true;
    
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Magento\Checkout\Model\Session
     */
    protected $orderSession;
    
    /**
     * @var Sellix\Pay\Helper\Data
     */
    protected $sellixpayHelper;
    
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList $directory_list
     */
    protected $directory_list;
    
    /**
     * @var \Magento\Framework\Filesystem\Driver\File $fileDriver
     */
    protected $fileDriver;
    
    /**
     * @var \Magento\Framework\HTTP\Client\Curl $curl
     */
    protected $curl;
    
    /**
     * @var string
     */
    protected $_formBlockType = \Sellix\Pay\Block\Form\Sellixpay::class;

    /**
     * @var string
     */
    protected $_infoBlockType = \Sellix\Pay\Block\Info\Sellixpay::class;
    
    /**
     * Constructor
     *
     * @param \Sellix\Pay\Helper\Data $sellixpayHelper
     * @param \Magento\Checkout\Model\Session $orderSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directory_list
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param \Magento\Directory\Helper\Data $directory
     *
     */
    public function __construct(
        \Sellix\Pay\Helper\Data $sellixpayHelper,
        \Magento\Checkout\Model\Session $orderSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\HTTP\Client\Curl $curl,
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
        $this->fileDriver = $fileDriver;
        $this->curl = $curl;

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
    
    /**
     * Get config value
     *
     * @param string $key
     *
     * @return string
     */
    public function getConfigValue($key)
    {
        $pathConfig = 'payment/' . $this->_code . "/" . $key;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue($pathConfig, $storeScope);
    }
    
    /**
     * Is method available
     *
     * @param Magento\Quote\Api\Data\CartInterface $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        if ($isAvailable) {
            $api_key = $this->getConfigValue('api_key', $quote ? $quote->getStoreId() : null);
            
            if (empty($api_key)) {
                $isAvailable = false;
            }
        }
        return $isAvailable;
    }
    
    /**
     * Can Use For Currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }
    
    /**
     * Is Initialize Needed
     *
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return true;
    }
    
    /**
     * Initialize Payment
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return bool
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }
    
    /**
     * Get Current Session Checkout Object
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckout()
    {
        return $this->orderSession;
    }
    
    /**
     * Get Checkout Quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    /**
     * Get Last order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getCheckout()->getLastRealOrder();
    }
        
    /**
     * Get Order Placed Redirect Url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('sellixpay/index/pay');
    }
    
    /**
     * Get Checkout Redirect Url
     *
     * @param array $params
     *
     * @return string
     */
    public function getCheckoutRedirectUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/pay', $params);
    }
    
    /**
     * Get Webhook Url
     *
     * @param array $params
     *
     * @return string
     */
    public function getWebhookUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/webhook', $params);
    }
    
    /**
     * Get Callback Url
     *
     * @param array $params
     *
     * @return string
     */
    public function getCallbackUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/callback', $params);
    }
    
    /**
     * Get Cancel Url
     *
     * @param array $params
     *
     * @return string
     */
    public function getCancelUrl($params = [])
    {
        return $this->urlBuilder->getUrl('sellixpay/index/cancel', $params);
    }
    
    /**
     * Get Checkout Success Url
     *
     * @return string
     */
    public function getCheckoutSuccessUrl()
    {
        return $this->urlBuilder->getUrl('checkout/onepage/success');
    }
    
    /**
     * Get Store Url
     *
     * @return string
     */
    public function getStoreUrl()
    {
        return $this->urlBuilder->getUrl();
    }
    
    /**
     * Log Message
     *
     * @param mixed $message
     *
     * @return void
     */
    public function log($message)
    {
        $debug = $this->getConfigValue('debug');
        if ($debug) {
            $filepath = $this->directory_list->getPath('log') . '/sellixpay.log';
            $this->fileDriver->filePutContents($filepath, date("Y-m-d H:i:s").": ", FILE_APPEND);
            $this->fileDriver->filePutContents(
                $filepath,
                print_r(// @codingStandardsIgnoreLine MEQP1.Security.DiscouragedFunction.Found
                    $message,
                    true
                ),
                FILE_APPEND
            );
            $this->fileDriver->filePutContents($filepath, "\n", FILE_APPEND);
        }
    }
    
    /**
     * Get Api Url
     *
     * @return void
     */
    public function getApiUrl()
    {
        return 'https://dev.sellix.io';
    }
    
    /**
     * Sellix Post Authenticated Json Request
     *
     * @param string $route
     * @param mixed $body
     * @param mixed $extra_headers
     * @param string $method
     *
     * @return array $response
     */
    public function sellixPostAuthenticatedJsonRequest($route, $body = false, $extra_headers = false, $method = "POST")
    {
        $server = $this->getApiUrl();

        $url = $server . $route;

        $uaString = 'Sellix Magento 2 (PHP ' . PHP_VERSION . ')';
        $apiKey = trim($this->getConfigValue('api_key'));
        $headers = [
            'Content-Type: application/json',
            'User-Agent: '.$uaString,
            'Authorization: Bearer ' . $apiKey
        ];

        if ($extra_headers && is_array($extra_headers)) {
            $headers = array_merge($headers, $extra_headers);
        }
        
        $this->log($url);
        $this->log($headers);
        $this->log($body);

        $this->curl->setOption(CURLOPT_HEADER, false);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, $headers);
        
        if ($method == 'POST') {
            $this->curl->post($url, json_encode($body));
        } else {
            $this->curl->get($url);
        }

        $response['body'] = $this->curl->getBody();
        $response['code'] = $this->curl->getStatus();
        $this->log($response['body']);
        $response['error'] = '';
        
        return $response;
    }
}
