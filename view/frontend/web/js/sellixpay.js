/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/payment/additional-validators',
], function ($,
        Component,
        placeOrderAction,
        additionalValidators,
        ) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Sellix_Pay/sellixpay',
            layout_dropdown: window.checkoutConfig.payment.sellixpay.layout_dropdown_status,
            layout_radio: window.checkoutConfig.payment.sellixpay.layout_radio_status
        },
        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },
        getData: function () {
            return {
                "method": 'sellixpay',
                "additional_data": {
                    'payment_gateway' : $("input[name='sellixpay_gateway']:checked").val()
                }
            };
        },
        getMethodsInDropdown: function () {
            return window.checkoutConfig.payment.sellixpay.dropdowndisplay;
        },
        getMethodsInRadio: function () {
            return window.checkoutConfig.payment.sellixpay.radiodisplay;
        },
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }
            var self = this,
                    placeOrder;
            
            if (window.checkoutConfig.payment.sellixpay.layout_radio_status) {
                if (!$("input[name='sellixpay_gateway']").is(':checked')) {
                    alert('Please select a Sellix payment method');
                    return false;
                }
            }
            
            if (additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);
                var paymentData = this.getData();
                if (window.checkoutConfig.payment.sellixpay.layout_dropdown_status) {
                    paymentData['additional_data']['payment_gateway'] = $("select[name='sellixpay_gateway']").val();
                }
                placeOrder = placeOrderAction(paymentData, false, this.messageContainer);

                $.when(placeOrder).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).done(this.afterPlaceOrder.bind(this));
                return true;
            }
            return false;
        },
        afterPlaceOrder: function () {
            var method = this.getCode();
            var urlRedirect = window.checkoutConfig.payment[method].redirectUrl;
            window.location.replace(urlRedirect);
        }
    });
});