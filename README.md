magento2-sellix-pay
======================

Sellix Pay Magento2 extension

Install
=======

1. Upload code to folder app/code/Sellix/Pay

2.  Enter following commands to install module:
    php bin/magento setup:upgrade
    php bin/magento setup:static-content:deploy

4. Enable and configure Sellix Pay in Magento Admin under Stores -> Configuration-> Sales -> Payment Methods -> Sellix Pay

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
- Added a new gateway: Cash App.
- Added a new option: merchant can enable their own branded sellix pay checked url to their customers.
- And updated Bitcoin Cash gateway value
- And updated perfectmoney gateway value
- Updated webhook to handle the 'PROCESSING' status received from sellix pay

= 1.0.3 =
- Removed layout selection, confirmations, sellix payment gateways enable/disable, and email configuration fields
- Removed sellix payment gateway selection UI in the frontend.
- Now it is redirected to gateway where customers will select the sub payment method selection