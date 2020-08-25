define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Apexx_Afterpay/payment/form',
                transactionResult: ''
            },

            getCode: function() {
                return 'afterpay_gateway';
            }
        });
    }
);