# Idenfy Customer Verification module for Magento 2

This plugin for Magento 2 will implement the [Idenfy API](https://www.idenfy.com/) in your store.

## Installation
**You can install this plugin using Composer:**

First add this repository to your composer file.
```shell
composer config repositories.idenfy git https://github.com/Skullsneeze/idenfy-magento2.git
```

Require the package.
```shell
composer require idenfy/module-customer-verification
```

## Enable the module
```shell
bin/magento module:enable Idenfy_CustomerVerification
bin/magento setup:di:compile
bin/magento setup:upgrade
```

## Verification Records
Verification responses are stored in the idenfy_verification table. The results can be viewed in the admin section by navigating to "Idenfy > Idenfy Customer Verification"

## Configuration
You can find all related configurations for this module by navigating to "Stores > Configuration > Idenfy > API Configuration" in the Magento admin section.

### Configuration Options
| Configuration | Description                                                                                                                                                           |
|---------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| API Key       | Used to authenticate requests made to the Idenfy API. See how to manage your API keys [here](https://documentation.idenfy.com/tutorials/admin-platform/ManageAPIKeys) |
| API Secret    | Used to authenticate requests made to the Idenfy API. See how to manage your API keys [here](https://documentation.idenfy.com/tutorials/admin-platform/ManageAPIKeys) |

## License
MIT license. For more information, see the [LICENSE](LICENSE.txt) file.
