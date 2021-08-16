define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'ngeniusonline',
                component: 'NetworkInternational_NGenius/js/view/payment/method-renderer/ngeniusonline'
            }
        );

        /**
    * Add view logic here if needed
    */
        return Component.extend({});
    }
);
