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
                type: 'afterpay_gateway',
                component: 'Apexx_Afterpay/js/view/payment/method-renderer/afterpay_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
