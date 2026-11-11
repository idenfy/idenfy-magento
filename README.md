# Idenfy Customer Verification module for Magento 2

This plugin for Magento 2 will implement the [Idenfy API](https://www.idenfy.com/) in your store.
Customers have to complete an Idenfy identity verification during checkout before any payment method becomes available.

## Requirements
- Magento 2.3.3 or later
- PHP 7.4 or later

## Installation
**You can install this plugin using Composer:**

First add this repository to your composer file.
```shell
composer config repositories.idenfy git https://github.com/idenfy/idenfy-magento.git
```

Require the package.
```shell
composer require idenfy/module-customer-verification
```

## Enable the module
```shell
bin/magento module:enable Idenfy_CustomerVerification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Run `setup:di:compile` after `setup:upgrade`: in production mode `setup:upgrade` removes previously generated code.

## Verification Records
Verification responses are stored in the idenfy_verification table. The results can be viewed in the admin section by navigating to "Idenfy > Idenfy Customer Verification"

## Configuration
You can find all related configurations for this module by navigating to "Stores > Configuration > Idenfy > API Configuration" in the Magento admin section.

### Configuration Options
| Configuration       | Config path                          | Description                                                                                                                                                                                       |
|---------------------|--------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Enable Module       | `idenfy/general/enabled`             | Enables customer verification (default: Yes). Verification is only enforced once the API Key and API Secret are set as well.                                                                    |
| API Key             | `idenfy/general/api_key`             | Used to authenticate requests made to the Idenfy API. See how to manage your API keys [here](https://documentation.idenfy.com/tutorials/admin-platform/ManageAPIKeys)                             |
| API Secret          | `idenfy/general/api_secret`          | Used to authenticate requests made to the Idenfy API. Stored encrypted.                                                                                                                         |
| Webhook Signing Key | `idenfy/general/webhook_signing_key` | Strongly recommended. When set, webhooks without a valid `Idenfy-Signature` header are rejected. Must match the *Signing key* of the webhook in the Idenfy dashboard. Stored encrypted.           |

The values can also be set from the command line, for example:
```shell
bin/magento config:set idenfy/general/webhook_signing_key "<signing key>"
```

## Webhook
The module passes its webhook URL to Idenfy with every verification session:
`https://<your store>/rest/<store code>/V1/idenfy/process-verification`.
The store therefore has to be reachable from the internet for verification results to arrive.

- Only final results (`"final": true`) change a customer's verification status. Preliminary results that will be followed by a manual review are logged and otherwise ignored.
- A webhook is only accepted for the verification session (`scanRef`) that is currently stored for the customer.
- To make sure webhooks really come from Idenfy, set a signing key in the Idenfy dashboard
  (Settings → System notifications → your webhook → Headers → Signing key) and enter the same value as **Webhook Signing Key**.
  See [Callback Signing](https://documentation.idenfy.com/security/callback-signing).

Webhook activity and API errors are logged to `var/log/idenfy.log`.

## License
MIT license. For more information, see the [LICENSE](LICENSE.txt) file.
