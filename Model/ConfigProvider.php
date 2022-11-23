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
    
    protected $assetRepo;
    
    public $images;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
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
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment'][$code]['redirectUrl'] = $this->methods[$code]->getCheckoutRedirectUrl();
                
                
                $dropdown = true;
                $radio = false;
                $payment_fields_layout = $this->methods[$code]->getConfigValue('payment_fields_layout');
                if ($payment_fields_layout) {
                    $dropdown = false;
                    $radio = true;
                } 
                $config['payment'][$code]['layout_dropdown_status'] = $dropdown;
                $config['payment'][$code]['layout_radio_status'] = $radio;
                
                $dropdown_html = '';
                $radio_html = '';
                if ($dropdown) {
                    $dropdown_html = $this->getDropdownHtml($this->methods[$code]);
                } else {
                    $radio_html = $this->getRadioHtml($this->methods[$code]);
                }
                $config['payment'][$code]['dropdowndisplay'] = $dropdown_html;
                $config['payment'][$code]['radiodisplay'] = $radio_html;
            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }
    
    public function getDropdownHtml($method)
    {
        $html = '<select name="sellixpay_gateway" class="sellix-payment-gateway-select">';

        if ($method->getConfigValue('bitcoin')) {
            $html .= '<option value="BITCOIN">Bitcoin (BTC)</option>';
        }
        if ($method->getConfigValue('ethereum')) {
            $html .= '<option value="EUTHEREUM">Ethereum (ETH)</option>';
        }
        if ($method->getConfigValue('bitcoin_cash')) {
            $html .= '<option value="BITCOINCASH">Bitcoin Cash (BCH)</option>';
        }
        if ($method->getConfigValue('litecoin')) {
            $html .= '<option value="LITECOIN">Litecoin (LTC)</option>';
        }
        if ($method->getConfigValue('concordium')) {
            $html .= '<option value="CONCORDIUM">Concordium (CCD)</option>';
        }
        if ($method->getConfigValue('tron')) {
            $html .= '<option value="TRON">Tron (TRX)</option>';
        }
        if ($method->getConfigValue('nano')) {
            $html .= '<option value="NANO">Nano (XNO)</option>';
        }
        if ($method->getConfigValue('monero')) {
            $html .= '<option value="MONERO">Monero (XMR)</option>';
        }
        if ($method->getConfigValue('ripple')) {
            $html .= '<option value="RIPPLE">Ripple (XRP)</option>';
        }
        if ($method->getConfigValue('solana')) {
            $html .= '<option value="SOLANA">Solana (SOL)</option>';
        }
        if ($method->getConfigValue('cronos')) {
            $html .= '<option value="CRONOS">Cronos (CRO)</option>';
        }
        if ($method->getConfigValue('binance_coin')) {
            $html .= '<option value="BINANCE_COIN">Binance Coin (BNB)</option>';
        }
        if ($method->getConfigValue('paypal')) {
            $html .= '<option value="PAYPAL">PayPal</option>';
        }
        if ($method->getConfigValue('stripe')) {
            $html .= '<option value="STRIPE">Stripe</option>';
        }
        
        if ($method->getConfigValue('usdt')) {
            if ($method->getConfigValue('usdt_erc20')) {
                $html .= '<option value="USDT:ERC20">USDT:ERC20</option>';
            }
            if ($method->getConfigValue('usdt_bep20')) {
                $html .= '<option value="USDT:BEP20">USDT:BEP20</option>';
            }
            if ($method->getConfigValue('usdt_trc20')) {
                $html .= '<option value="USDT:TRC20">USDT:TRC20</option>';
            }
        }
        if ($method->getConfigValue('usdc')) {
            if ($method->getConfigValue('usdc_erc20')) {
                $html .= '<option value="USDC:ERC20">USDC:ERC20</option>';
            }
            if ($method->getConfigValue('usdc_bep20')) {
                $html .= '<option value="USDC:BEP20">USDC:BEP20</option>';
            }
        }
        
        if ($method->getConfigValue('binance_pay')) {
            $html .= '<option value="BINANCE_PAY">Binance Pay (BUSD)</option>';
        }

        if ($method->getConfigValue('skrill')) {
            $html .= '<option value="SKRILL">Skrill</option>';
        }
        if ($method->getConfigValue('perfectmoney')) {
            $html .= '<option value="PERFECTMONEY">PerfectMoney</option>';
        }
        $html .= '</select>';
        return $html;
    }
    
    public function getRadioHtml($method)
    {
        $html = '';

        if ($method->getConfigValue('bitcoin')) {
            $html .= $this->getPaymentHtml('bitcoin', 'BITCOIN', 'Bitcoin (BTC)');
        }
        if ($method->getConfigValue('ethereum')) {
            $html .= $this->getPaymentHtml('ethereum', 'EUTHEREUM', 'Ethereum (ETH)');
        }
        if ($method->getConfigValue('bitcoin_cash')) {
            $html .= $this->getPaymentHtml('bitcoin-cash', 'BITCOINCASH', 'Bitcoin Cash (BCH)');
        }
        if ($method->getConfigValue('litecoin')) {
            $html .= $this->getPaymentHtml('litecoin', 'LITECOIN', 'Litecoin (LTC)');
        }
        if ($method->getConfigValue('concordium')) {
            $html .= $this->getPaymentHtml('concordium', 'CONCORDIUM', 'Concordium (CCD)');
        }
        if ($method->getConfigValue('tron')) {
            $html .= $this->getPaymentHtml('tron', 'TRON', 'Tron (TRX)');
        }
        if ($method->getConfigValue('nano')) {
            $html .= $this->getPaymentHtml('nano', 'NANO', 'Nano (XNO)');
        }
        if ($method->getConfigValue('monero')) {
            $html .= $this->getPaymentHtml('monero', 'MONERO', 'Monero (XMR)');
        }
        if ($method->getConfigValue('ripple')) {
            $html .= $this->getPaymentHtml('ripple', 'RIPPLE', 'Ripple (XRP)');
        }
        if ($method->getConfigValue('solana')) {
            $html .= $this->getPaymentHtml('solana', 'SOLANA', 'Solana (SOL)');
        }
        if ($method->getConfigValue('cronos')) {
            $html .= $this->getPaymentHtml('cronos', 'CRONOS', 'Cronos (CRO)');
        }
        if ($method->getConfigValue('binance_coin')) {
            $html .= $this->getPaymentHtml('binance', 'BINANCE_COIN', 'Binance Coin (BNB)');
        }
        if ($method->getConfigValue('paypal')) {
            $html .= $this->getPaymentHtml('paypal', 'PAYPAL', 'PayPal');
        }
        if ($method->getConfigValue('stripe')) {
            $html .= $this->getPaymentHtml('stripe', 'STRIPE', 'Stripe');
        }
        
        if ($method->getConfigValue('usdt')) {
            if ($method->getConfigValue('usdt_erc20')) {
                $html .= $this->getPaymentHtml('usdt', 'USDT:ERC20', 'USDT:ERC20');
            }
            if ($method->getConfigValue('usdt_bep20')) {
                $html .= $this->getPaymentHtml('usdt', 'USDT:BEP20', 'USDT:BEP20');
            }
            if ($method->getConfigValue('usdt_trc20')) {
                $html .= $this->getPaymentHtml('usdt', 'USDT:TRC20', 'USDT:TRC20');
            }
        }
        if ($method->getConfigValue('usdc')) {
            if ($method->getConfigValue('usdc_erc20')) {
                $html .= $this->getPaymentHtml('usdc', 'USDC:ERC20', 'USDC:ERC20');
            }
            if ($method->getConfigValue('usdc_bep20')) {
                $html .= $this->getPaymentHtml('usdc', 'USDC:BEP20', 'USDC:BEP20');
            }
        }
        
        if ($method->getConfigValue('binance_pay')) {
            $html .= $this->getPaymentHtml('binance', 'BINANCE_PAY', 'Binance Pay (BUSD)');
        }
        if ($method->getConfigValue('skrill')) {
            $html .= $this->getPaymentHtml('skrill', 'SKRILL', 'Skrill');
        }
        if ($method->getConfigValue('perfectmoney')) {
            $html .= $this->getPaymentHtml('pm', 'PERFECTMONEY', 'PerfectMoney');
        }
        
        return $html;
    }
    
    public function getPaymentHtml($code, $value, $title)
    {
        $css = $code;
        if ($code == 'paypal') {
            $css = $code.'-css';
        }
        $html = '<div class="payment-labels-container">
                    <div class="payment-labels '.$css.'">
                        <label class="'.$css.'">
                            <input type="radio" name="sellixpay_gateway" value="'.$value.'" />
                            <img src="'.$this->assetRepo->getUrl('Sellix_Pay::images/'.$code.'.png').'" alt="'.$title.'" style="border-radius: 0px;" width="20" height="20"> '.$title.' 
                        </label>
                    </div>
                </div>';
        return $html;
    }
}
