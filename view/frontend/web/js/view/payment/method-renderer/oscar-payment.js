define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url'
    ],
    function (Component, quote, fullScreenLoader, redirectOnSuccessAction, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Oscar_Payment/payment/oscar-payment'
            },

            getCode: function() {
                return 'oscar_payment';
            },

            isActive: function() {
                return true;
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {}
                };
            },

            afterPlaceOrder: function() {
                redirectOnSuccessAction.redirectUrl = url.build('oscar_payment/payment/redirect');
                this.redirectAfterPlaceOrder = true;
            }
        });
    }
); 