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
                template: 'Oscar_Payment/payment/oscar-payment',
                code: 'oscar_payment'
            },

            getCode: function() {
                return this.code;
            },

            isActive: function() {
                return true;
            },

            isAvailable: function() {
                return true;
            },

            getTitle: function() {
                return 'Oscar Payment';
            },

            getDescription: function() {
                return 'Pay using our secure payment gateway';
            },

            getData: function() {
                return {
                    'method': this.getCode(),
                    'additional_data': {}
                };
            },

            afterPlaceOrder: function() {
                redirectOnSuccessAction.redirectUrl = url.build('oscar_payment/payment/redirect');
                this.redirectAfterPlaceOrder = true;
            },

            validate: function() {
                return true;
            }
        });
    }
); 