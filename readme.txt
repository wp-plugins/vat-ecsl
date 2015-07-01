=== VAT EC Sales List ===
Author URI: http://www.wproute.com/
Plugin URI: http://www.wproute.com/wp-vat-ec-sales-list-submissions/
Contributors: bseddon
Tags: VAT, HMRC, ECSL, EC Sales List, VAT101, tax, EU, UK, WooCommerce
Requires at least: 3.9.2
Tested up to: 4.2
Stable Tag: 1.0.13
License: GNU Version 2 or Any Later Version

Create submissions for EC Sales List returns (VAT101) to be sent directly to HMRC from Easy Digital Downloads &amp; Woo Commerce sales records.

== Description ==

**This plugin is only applicable to VAT registered businesses based in the UK that make sales to businesses (B2B) in other EU member states**

Each quarter, or even month, UK businesses must submit to the UK tax authority (HMRC) an **EC Sales List** to document sales made to businesses in other EU member states. This plug-in integrates with Easy Digital Downloads and/or Woo Commerce so it is able retrieve relevant sales records from which to create the quarterly (or monthly) return.

**Note:** This plugin is free to download and you will be able to use it to create and test EC Sales List submissions. To submit returns directly to HMRC you will need to purchase a credit [from our web site](http://www.wproute.com/2015/01/wp-vat-ec-sales-list-submissions/ "Buy credits"): &pound;5 for a single submission; &pound;18 for 4 (quarterly) submissions; &pound;50 for 12 (monthly) submissions.

= Features =

**Select your e-commerce package**

  * Easy Digital Downloads or
  * Woo Commerce

**Create quarterly or monthly submissions**

  * Select the transactions to include
  * The plugin will only present sales to EU businesses outside the UK so you cannot select invalid sales records
  * Specify the quarter for the submission
  * Test your EC Sales List return submission before sending it live

**Roles**

  * Provides roles you can use to control who is able to create and submit returns
  * Give your accountant limited access to be able to submit returns
  * Or only give your accountant the ability to view returns.

**Videos**

[Watch videos](http://www.wproute.com/2015/01/wp-vat-ec-sales-list-submissions/ "Videos showing the plug-in working") showing how to setup the plugin, create a submission and send the return to HMRC
	
**Submit EC Sales list returns directly to HMRC**

[Buy credits](http://www.wproute.com/2015/01/wp-vat-ec-sales-list-submissions/ "Buy credits") to submit your EC Sales List return directly to HMRC.

== Frequently Asked Questions ==

= Q. Do I need to buy credits to use the plugin? =
A. You are able to create a submission that will list the transactions to be included in a monthly or quarterly return without buying credits. However to send a return directly to HMRC you will need to buy a credit.

= Q. Do I need to buy a credit to test a submission? =
A. No, you are able to test sending an EC Sales List return before you buy a credit.

== Installation ==

Install the plugin in the normal way then select the settings option from the ECSL menu added to the main WordPress menu.  Detailed [configuration and use instructions](http://www.wproute.com/wp-vat-ec-sales-list-submissions/) can be found on our web site.

**Requires**

This plugin requires that you capture VAT information in a supported format such as the format created by the [Lyquidity VAT plugin for EDD](http://www.wproute.com/ "VAT for EDD") 
or the [Woo Commerce EU VAT Compliance plugin "Premium version"](https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/) or
or the [WooCommerce EU VAT Assistant](https://wordpress.org/plugins/woocommerce-eu-vat-assistant/).

== Screenshots ==

1. The first task is to define the settings that are common to all submissions.
2. The second task is to select the e-commerce package you are using.
3. The main screen shows a list of the existing submissions.
4. New definitions are created by specifying the correct header information, most of which is taken from the settings, and also select the sales transactions that should be includedin the submission
5. Any transaction may comprise products which are goods or a service and these transaction types must be reflected in the EC Sales List return.  The plugin adds a meta box to the download (EDD) or product (Woo Commerce) so the indicator can be defined.
6. A credit can be purchased to perform the submission to HMRC. The credit license key can be tested for validity.  The submission can be tried in test mode before a live submission is attempted and a credit consumed.

== Changelog ==

= 1.0 =
Initial version released

= 1.0.3 =
Added currency translation to convert a UK site denominated in, say, USD to GBP.
Added tests to make sure plugin files cannot be executed independently.
Added additional checks to prevent actions being repeated if subsequent attempts would be invalid.

= 1.0.4

Added export

= 1.0.5

Small change to prevent js and css files being added to the front end

= 1.0.6

Changes to address problems with translatability

= 1.0.7

Added support for EU VAT Assistant for WooCommerce from Aelia
Added notices that VAT plugins must be installed and activated

= 1.0.8 =

Fixed the tests to confirm the existence of the Lyquidity plugin (EDD) or the Simba or EU VAT Assistant plugin (WooCommerce)

= 1.0.9 =

Fixed a problem with the EDD integration which was missing a date element.

= 1.0.10 =

Updated references to the service site

= 1.0.11 =

Updated add_query_arg calls to escape them as recommended by the WordPress advisory

= 1.0.12 =

Fixed text domain errors

= 1.0.13 =

Added note alongside the postcode to warn users the postcode must not contain a space.  KT35EE is OK.  KT3 5EE will cause an HMRC sumbission validaton failure.

== Upgrade Notice ==

Nothing here
