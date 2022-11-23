
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'sellixpay',
                component: 'Sellix_Pay/js/sellixpay'
            }
        );
        return Component.extend({});
    }
);