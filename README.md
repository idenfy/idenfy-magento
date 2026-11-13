# Idenfy Customer Verification module for Magento 2

This plugin for Magento 2 will implement the [Idenfy API](https://www.idenfy.com/) in your store.
Customers have to complete an Idenfy identity verification before they can place an order.

## Requirements
- Magento Open Source or Adobe Commerce 2.4.0 or later
- PHP 7.4 or later

## Installation
**From the Adobe Commerce Marketplace:** order the free iDenfy extension on the
[Adobe Commerce Marketplace](https://commercemarketplace.adobe.com), then require the package. Composer uses your
[Adobe Commerce authentication keys](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/prerequisites/authentication-keys).
```shell
composer require idenfy/module-customer-verification
```

**From GitHub:** add this repository to your composer file, then require the package.
```shell
composer config repositories.idenfy git https://github.com/idenfy/idenfy-magento.git
composer require idenfy/module-customer-verification
```

## Enable the module
```shell
bin/magento module:enable Idenfy_CustomerVerification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:flush
```

Run `setup:di:compile` after `setup:upgrade`: in production mode `setup:upgrade` removes previously generated code.

## Upgrading
```shell
bin/magento maintenance:enable
composer update idenfy/module-customer-verification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:flush
bin/magento maintenance:disable
```

API credentials and existing verifications are kept. When upgrading from 1.0.x, read the [changelog](CHANGELOG.md):
since 1.2.0 the verification runs in a modal on the checkout and payment methods are no longer hidden, and you should
set a **Webhook Signing Key**.

## How it works
1. When an unverified customer opens the checkout, a verification modal opens. The customer can close it and continue
   with the checkout as usual.
2. When an unverified customer clicks **Place Order** (or a payment button such as PayPal, Apple Pay or Google Pay),
   the modal opens again instead of placing the order.
3. The verification runs inside the modal in an embedded iFrame, so the customer stays on your store.
4. Idenfy sends the result to the store's webhook. As soon as it is approved, the modal closes and the customer can
   place the order. Payment details already entered are kept.

If the result is not final yet (manual review), the modal says so and the customer can check again later. After a
failed verification the customer can try again.

The verification is enforced on the server, not by the modal:
- Orders of unverified customers are rejected when they are placed. This covers the checkout, express buttons
  (e.g. PayPal on the cart page), one-step checkout extensions, the REST API and GraphQL. When the storefront receives
  this rejection, the modal opens again.
- Orders created by an admin, or through the API with an admin or integration token, are not affected.

Logged-in customers are verified once. Guests are verified per cart.

## Verification Records
Verification responses are stored in the idenfy_verification table. The results, with their status and when they were
created and last updated, can be viewed in the admin section by navigating to "Idenfy > Idenfy Customer Verification".

Unfinished verifications are resumed instead of starting a new session each time the customer opens the modal, so no
verification credits are wasted. A new session is only started after a failed attempt, or after 30 minutes.

## Configuration
You can find all related configurations for this module by navigating to "Stores > Configuration > Idenfy > API Configuration" in the Magento admin section.

### General
| Configuration       | Config path                          | Description                                                                                                                                                                                       |
|---------------------|--------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Enable Module       | `idenfy/general/enabled`             | Enables customer verification (default: Yes). Verification is only enforced once the API Key and API Secret are set as well.                                                                    |
| API Key             | `idenfy/general/api_key`             | Used to authenticate requests made to the Idenfy API. See how to manage your API keys [here](https://documentation.idenfy.com/guides/dashboard/settings/api-keys)                                |
| API Secret          | `idenfy/general/api_secret`          | Used to authenticate requests made to the Idenfy API. Stored encrypted.                                                                                                                         |
| Webhook Signing Key | `idenfy/general/webhook_signing_key` | Strongly recommended. When set, webhooks without a valid `Idenfy-Signature` header are rejected. Must match the *Signing key* of the webhook in the Idenfy dashboard. Stored encrypted.           |

### Checkout Experience
| Configuration                   | Config path                                   | Scope      | Description                                                                                                                                                            |
|---------------------------------|-----------------------------------------------|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Integration Mode                | `idenfy/storefront/integration_mode`          | Website    | `iframe` (default): verification in an iFrame inside the modal. `redirect`: the customer goes to Idenfy and returns to the page they started on.                      |
| Open Verification Automatically | `idenfy/storefront/auto_open`                 | Website    | Yes (default): the modal opens as soon as an unverified customer reaches the checkout. No: the modal only opens when the customer places the order. Either way it opens again on Place Order. |
| Allow Closing the Modal         | `idenfy/storefront/closable`                  | Website    | Yes (default): the customer can close the modal that opens on the checkout. No: it can only be left by verifying or going back to the cart. |
| Idenfy Branding Theme ID        | `idenfy/storefront/theme_id`                  | Website    | Optional ID of a KYC branding theme from the Idenfy dashboard (Settings → Branding) to style the verification screens.                                                  |
| Modal Title / Modal Text        | `idenfy/storefront/modal_title`, `modal_text` | Store view | Texts of the modal. Empty uses the default, translated texts.                                                                                                           |
| Button Label                    | `idenfy/storefront/button_label`              | Store view | Label of the button that starts the verification. Empty uses "Start verification".                                                                                     |
| Button Style                    | `idenfy/storefront/button_style`              | Store view | `theme` (default) uses your theme's primary button. `custom` applies the colours and corner radius below.                                                               |
| Button Background / Text Colour | `idenfy/storefront/button_background_color`, `button_text_color` | Store view | Hex colours, e.g. `#1979C3`. Empty keeps the theme colour.                                                                              |
| Button Corner Radius (px)       | `idenfy/storefront/button_border_radius`      | Store view | 0 to 50. Empty keeps the theme radius.                                                                                                                                  |
| Additional Checkout Pages       | `idenfy/storefront/additional_checkout_pages` | Website    | See [One-step checkout extensions](#one-step-checkout-extensions).                                                                                                    |

The verification screens are shown in the language of the store view when Idenfy supports it.

The values can also be set from the command line, for example:
```shell
bin/magento config:set idenfy/general/webhook_signing_key "<signing key>"
bin/magento config:set idenfy/storefront/integration_mode redirect
```

## One-step checkout extensions
Orders of unverified customers are rejected on the server with any checkout. To also show the verification modal:

- Extensions that use Magento's checkout page (`/checkout`) work without configuration.
- Extensions that run on their own page: enter the page's full action name in **Additional Checkout Pages**, one per
  line (for example `onestepcheckout_index_index`; it is the layout handle of the page). The modal then opens there as
  well.

Place Order opens the modal again in every checkout that runs Magento's additional payment validators before placing
the order, as Magento's own checkout and most payment methods do. Otherwise the modal opens again as soon as the server
rejects the order.

Themes that do not use Magento's RequireJS/Knockout frontend (e.g. Hyvä) need a compatibility module to show the modal.
Verification is still enforced on the server.

## Content Security Policy
Magento 2.4.7 and later enforce the Content Security Policy on the checkout. The module allows
`https://ui.idenfy.com` as a frame source in `etc/csp_whitelist.xml`. If your Idenfy account uses a custom UI domain,
set it with `bin/magento config:set idenfy/general/ui_base_url https://<your domain>` and allow that domain as
`frame-src` in your own `csp_whitelist.xml`.

If customers have trouble with camera access inside the iFrame, Idenfy can enable a *Continue in new tab* fallback on
your account, or switch **Integration Mode** to Redirect.

## Webhook
The module passes its webhook URL to Idenfy with every verification session:
`https://<your store>/rest/<store code>/V1/idenfy/process-verification`.
The store therefore has to be reachable from the internet for verification results to arrive.

- Only final results (`"final": true`) change a customer's verification status. Preliminary results that will be
  followed by a manual review mark the verification as "in review".
- A webhook is only accepted for the verification session (`scanRef`) that is currently stored for the customer.
- To make sure webhooks really come from Idenfy, set a signing key in the Idenfy dashboard
  (Settings → System notifications → your webhook → Headers → Signing key) and enter the same value as **Webhook Signing Key**.
  See [Callback Signing](https://documentation.idenfy.com/security/callback-signing).

Webhook activity and API errors are logged to `var/log/idenfy.log`.

## REST endpoints used by the storefront
| Endpoint                                  | Description                                                                                          |
|-------------------------------------------|------------------------------------------------------------------------------------------------------|
| `POST /V1/idenfy/verification-status`     | Status of the current checkout session: `not_required`, `verified`, `not_started`, `pending`, `reviewing` or `failed`. |
| `POST /V1/idenfy/get-redirect-url`        | Starts or resumes the verification and returns the URL for the iFrame or the redirect. Accepts `restart` and `returnUrl`. |
| `POST /V1/idenfy/needs-verification`      | Kept for backwards compatibility.                                                                    |

## License
MIT license. For more information, see the [LICENSE](LICENSE.txt) file.
