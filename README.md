# Magento Rapidez StandaloneCheckout

This Magento module will send users with a valid cart to the Rapidez checkout instead of the Magento checkout so Rapidez can be used as a standalone checkout.

## Installation

### Install the package using composer
 
```bash
composer install rapidez/magento2-standalone-checkout
```

### Enable the module

```bash
bin/magento module:enable Rapidez_StandaloneCheckout
```

## Configuration

All you need to do is set the Rapidez url in the standalone checkout config.
You can do so under `Stores > Configuration > Rapidez > Standalone Checkout > Rapidez Url`

## Considerations

### Registration

Rapidez' Registration feature will not automatically log you in to Magento

### Rapidez header

By default Rapidez shows a full header and menu on the success page, you might want to consider showing the limited header instead.
