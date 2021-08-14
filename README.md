## How to install

### Install via composer (Recommended)

```
composer require hawksama/magento-2-pdf-invoice-generator;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
```

## How to upgrade

```
composer update hawksama/magento-2-pdf-invoice-generator;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
```


## FAQs

#### Q: My site is down
A: First, disable the module to be sure that the store is working:
```
php bin/magento module:disable Hawksama_Invoice ;
php bin/magento cache:flush;
php bin/magento cache:clean;
```


## Support
- manue971@icloud.com
