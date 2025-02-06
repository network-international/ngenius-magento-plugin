# Changelog

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
