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
                type: 'oscar_payment',
                component: 'Oscar_Payment/js/view/payment/method-renderer/oscar-payment'
            }
        );

        return Component.extend({});
    }
); 