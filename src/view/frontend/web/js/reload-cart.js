require([
    'Magento_Customer/js/customer-data'
 ], function (customerData) {
    /**
     * Update the cart information if the previous page did not originate from the Magento domain.
     * In case an order has been placed.
     */
     const sections = ['cart'];

     if(document.referrer && document.referrer.indexOf(location.protocol + "//" + location.host) === 0) {
        return;
     }

    const cartData = customerData.get('cart');

    customerData?.getInitCustomerData()?.done(function () {
        if (!cartData()?.items || cartData().items?.length === 0) {
            return;
        }

        customerData.invalidate(sections);
        customerData.reload(sections, true);
    })
 });
