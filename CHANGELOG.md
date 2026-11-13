# Changelog

## [[1.4.2]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.4.2)

### Fixed

- Continue Shopping link beneath the Pay button on the hosted payment page. The order request
  now sends a cancel URL and the label 'Continue Shopping', so the link is displayed.

## [[1.4.1]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.4.1)

### Fixed

- Partial refund processing logic to correctly distinguish between fully refunded and partially refunded transactions.
  Refund status determination is now based on the original captured amount rather than remaining captured values,
  ensuring partially refunded orders are no longer incorrectly marked as fully refunded.
- Cron job configuration: Disabled separate process execution (`use_separate_process` changed from 1 to 0) to resolve
  potential process isolation issues.
- Error handling in Payment controller: Now checks error status from both `NgeniusApiService` and `OrderStatusService`
  for more comprehensive error detection.
- AWAIT3DS state handling: Included AWAIT3DS state in cron queries for started orders to properly process abandoned
  orders awaiting 3DS authentication.
- Order state persistence: Implemented direct database updates for order state/status in scenarios where DataObject
  save methods are unavailable, ensuring state changes are properly recorded.
- Observer safety checks: Added null/empty data validation in `OrderAuthCaptured` observer to prevent errors when
  processing orders with missing or incomplete data.

### Improved

- State management: Added `getNgeniusState()` and `setNgeniusState()` methods to `NgeniusApiService` and
  `OrderStatusService` for better state tracking and consistency across payment processing workflows.
- Error detection: Added `getIsError()` methods to both API and order status services, providing standardized error
  state access throughout the payment flow.

## [[1.4.0]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.4.0)

### Added

- Compatibility update for Magento 2.4.7 and PHP 8.2.
- Onboarding Link in Plugin Interface: Added an accessible link within the Magento plugin interface that redirects
  merchants to the onboarding site for e-commerce payment solutions. The link is prominently placed in the plugin's
  settings panel, ensuring easy access for merchants to initiate the onboarding process.

## [[1.3.0]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.3.0)

### Added

- Integrated the N-Genius PayByLink feature, allowing customers to pay via a link sent to their email or phone.

## [[1.2.0]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.2.0)

### Added

- Updated to the Network logo.
- Support for Adobe Commerce Marketplace.

### Fixed

- Fixed a bug that caused errors during checkout when using multi-shipping functionality.

### Tested

- Compatibility with N-Genius Common Library v1.3.0.

## [[1.1.7]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.7)

### Fixed

- Corrected status flow for post-shipment invoicing.

## [[1.1.6]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.6)

### Fixed

- Arithmetic on floating-point numbers bug.
- Duplicate order processing after cron has run.

### Tested

- Compatibility with Magento 2.4.7 and PHP 8.1.

## [[1.1.5]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.5)

### Improved

- Improve cron job process workflow and multi-site support.

### Fixed

- Bug fixes and improvements.

## [[1.1.4]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.4)

### Added

- Add VISA additional mandatory fields to the Order API Request Body.

### Changed

- Don't allow OrderCancelAfter Observer to trigger for other payment methods.
- Don't allow Cron to process orders created by other payment methods.
- Change the cron table from `ngenius_networkinternational` to `ngenius_networkinternational_sales_order` to avoid
  processing historic orders.

### Fixed

- Fix Cron order processing of abandoned APM methods in a state of AWAIT_3DS.
- Improve payment action support for China Union Pay.

## [[1.1.3]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.3)

### Added

- Cancel abandoned orders after one hour using the cron.
- Add the ability to debug cron job order processing.
- Add support for XOF.

### Improved

- Improve support for Samsung Pay Refunds.

## [[1.1.2]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.2)

### Fixed

- Fix schema index attribute causing deprecated functionality warning.

### Added

- Add support for BHD and KWD currency decimals.

## [[1.1.1]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.1)

### Changed

- Align code with Magento code standards (phpcs).

### Fixed

- Fix duplicate mail notifications when re-loading the order success page.
- Fix store credit reverting twice on certain order status settings.

## [[1.1.0]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.1.0)

### Fixed

- Fix order status not changing to 'Complete' when shipped using 'Capture Offline'.
- Fix cron query timing out.

### Added

- Allow N-Genius Refund Statuses to be configurable.
- Add merchant-defined data for plugin name and version.

## [[1.0.9]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.9)

### Added

- Add option "Send Email on Order Creation".

### Changed

- Force Magento payment action to 'authorize'.

### Fixed

- Fix order email not sending for COD payment method.

## [[1.0.8]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.8)

### Added

- Add support for custom failed order statuses and states.
- Add option to disable sending the invoice email.

### Fixed

- Fix issues with the order status history for the initial order state.
- Fix issues with multi-store website scopes for settings.

### Improved

- Improve reliability of payment actions on different servers.

## [[1.0.7]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.7)

### Added

- Add support for custom success and pending order statuses and states.

## [[1.0.6]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.6)

### Added

- Add type check for commands before attempting to run.

## [[1.0.5]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.5)

### Fixed

- Handle deprecated functionality `str_replace()` passing null to parameter.
- Don't allow order email for invalid order indexes.

## [[1.0.4]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.4)

### Fixed

- Fix 'command doesn't exist' error when Magento `payment_action` does not save.

## [[1.0.3]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.3)

### Changed

- Refactor to use common classes and composer.

### Fixed

- Bugs fixes and improvements.

## [[1.0.2]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.2)

### Added

- Add support for Magento 2.4.5 and PHP 8.1.
- Add Outlet 2 Reference ID and Currencies override feature.
- Add/update default configuration values.

### Fixed

- Bug fixes and code quality improvements.

## [[1.0.1]](https://github.com/network-international/ngenius-magento-plugin/releases/tag/1.0.1)

### Added

- Initial version.
