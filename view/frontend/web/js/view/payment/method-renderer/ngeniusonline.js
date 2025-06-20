define(
  ['jquery', 'Magento_Checkout/js/view/payment/default', 'mage/url'],
  function ($, Component, url){
    'use strict';

    return Component.extend({
      defaults: {
        template               : 'NetworkInternational_NGenius/payment/ngeniusonline',
        redirectAfterPlaceOrder: false
      },

      getLogoSrc: function (){
        return window.location.origin + '/pub/static/frontend/Magento/luma/en_US/NetworkInternational_NGenius/images/ngenius_logo.png';
      },

      afterPlaceOrder: function (){
        $('#ngenius_redirect_msg').show();
        window.location.replace(url.build('networkinternational/ngeniusonline/redirect'));
      }
    });
  });
