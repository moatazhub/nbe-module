define([
  'Magento_Checkout/js/view/payment/default',
  'jquery',
  'Magento_Checkout/js/model/payment/additional-validators',
  'mage/url',
  'Magento_Checkout/js/action/place-order',
  'Magento_Checkout/js/model/full-screen-loader',
  //'https://test-nbe.gateway.mastercard.com/static/checkout/checkout.min.js',
  //'my_module',
], function (
  Component,
  $,
  additionalValidators,
  url,
  placeOrderAction,
  fullScreenLoader,
  // Checkout
  myCheckout
) {
  return Component.extend({
    defaults: {
      template: 'Ahly_Payments/payment/online',
      success: false,
      iframe_url: null,
      owner: null,
      cards: null,
      detail: null,
    },
    afterPlaceOrder: function (data, event) {
      var self = this;
      fullScreenLoader.startLoader();
      $.ajax({
        type: 'POST',
        url: url.build('ahly_pay/Methods/OnlineMethod'),
        data: data,
        success: function (response) {
          fullScreenLoader.stopLoader();
          if (response.success) {
            console.log('afterPlaceOrder:success');
            console.log(response);
            self.renderPayment(response);
          } else {
            console.log('afterPlaceOrder:error123');
            console.log(response);
            self.renderErrors(response);
          }
        },
        error: function (response) {
          console.log('afterPlaceOrder:error456');
          console.log(response);
          fullScreenLoader.stopLoader();
          self.renderErrors(response);
        },
      });
    },
    placeOrder: function (data, event) {
      if (event) {
        var script = document.createElement('script');
        script.src =
          'https://test-nbe.gateway.mastercard.com/static/checkout/checkout.min.js';
        //'Ahly_Payments/view/frontend/web/js/view/payment/world.js';
        script.type = 'text/javascript';
        //script.charset = 'UTF-8';
        script.crossorigin = 'anonymous';
        //script.dataset.error = 'https://hanimex.shop/contact';
        // script.dataset.complete = 'completeCallback'; //url.build('ahly_pay/callback/online');
        //'https://hanimex.shop/ahly_pay/callback/online';
        document.body.appendChild(script);
        event.preventDefault();
      }

      if (additionalValidators.validate()) {
        placeOrder = placeOrderAction(
          this.getData(),
          false,
          this.messageContainer
        );

        $.when(placeOrder).done(this.afterPlaceOrder.bind(this));
        return true;
      }

      return false;
    },
    renderPayment: function (data) {
      Checkout.configure({
        session: {
          id: data.session_id,
        },
      });

      Checkout.showPaymentPage();
    },

    renderErrors: function (data) {
      window.alert(data.detail);
      fullScreenLoader.stopLoader();
      $('body').css({
        overflow: 'hidden',
      });

      $('#online-container').show(250, function () {
        $('#online-errors').show(250, function () {
          $('#online-errors .errors').show().html(data.detail);
        });
      });
    },
    getData: function () {
      return { method: this.item.method };
    },
  });
});
