# Changelog

## 1.2.0

### Added
- Verification modal on the checkout, with the Idenfy verification embedded in an iFrame. The customer no longer
  leaves the store. This is the new default; the redirect flow is still available as **Integration Mode → Redirect**.
- The modal opens automatically for unverified customers (**Open Verification Automatically**) and can be closed
  (**Allow Closing the Modal**). It opens again when an unverified customer clicks Place Order or a payment button,
  and whenever the server rejects an order because the customer is not verified.
- Orders of unverified customers are rejected when they are placed. This covers the checkout, express buttons,
  one-step checkout extensions, REST and GraphQL. Orders created by an admin, or through the API with an admin or
  integration token, are not affected.
- **Checkout Experience** settings: modal title and text, button label, button style (theme default or custom colours
  and corner radius) and an Idenfy branding theme ID.
- **Additional Checkout Pages** setting to show the modal on one-step checkout extensions that use their own page.
- The verification UI is shown in the language of the store view, where Idenfy supports it.
- Verification status, creation and update time in the verification records and in the admin grid.
- Content Security Policy entry allowing the Idenfy UI in a frame (required on Magento 2.4.7+).
- REST endpoint `POST /V1/idenfy/verification-status`.

### Changed
- Payment methods are no longer hidden from unverified customers. Customers go through the checkout as usual and
  verify when they place the order; the order itself is rejected on the server until they are verified.
- An unfinished verification is resumed instead of starting a new session each time the customer clicks the button,
  so no verification credits are wasted. After a failed attempt a new session is started.
- While a verification is under manual review, no new session can be started.
- In redirect mode the customer returns to the page they started on (same store only), so one-step checkouts on their
  own URL work as well.

### Fixed
- `composer.json` declares Magento 2.4.0 or later again (the checkout template requires it) and the MIT license as a
  valid identifier.

## 1.1.0
Not released separately; included in 1.2.0.

- Fixed the error `Impossible to process constructor argument ... Class "Idenfy\customerVerification\Model\Action\GetClientId"
  does not exist` on the checkout and in the REST API (wrong namespace casing in 1.0.1–1.0.4).
- Webhook signature validation, verification flow and admin fixes.
- Requires PHP 7.4 or later.
