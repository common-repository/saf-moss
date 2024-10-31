=== SAF MOSS ===
Author URI: http://www.wproute.com/
Plugin URI: http://www.wproute.com/standard-audit-file-saf-moss/
Contributors: bseddon
Tags: VAT, HMRC, MOSS, M1SS, audit, SAF, Standard Audit File, SAF, SAT-MOSS, tax, EU, UKdigital vat, Easy Digital Downloads, edd, edd tax, edd vat, eu tax, eu vat, eu vat compliance, european tax, european vat, iva, iva ue, Mehrwertsteuer, mwst, taux de TVA, tax, TVA, VAT, vat compliance, vat moss, vat rates, vatmoss, WooCommerce
Requires at least: 3.9.2
Tested up to: 4.9
Stable Tag: 1.0.13
License: GNU Version 2 or Any Later Version

Create a Standard Audit File (SAF) of MOSS EDD or WooCommerce sales records and output as an Xml formatted file compliant with EC SAF-MOSS schema.

== Description ==

The tax authority of any EU member state may request an audit of the sales of any EU business.  The EU tax authorities 
have agreed a standard audit file (SAF) format for the information necessary for an audit so it can be supplied electronically: 
the so-called [SAF-MOSS XML schema](http://ec.europa.eu/taxation_customs/resources/documents/taxation/vat/how_vat_works/telecom/saf_moss_xsd_documentation_v11_final.zip).  This plugin allows you to specify a date range from which sales records
will be extracted from your EDD or WooCommerce shop and stored in a file that is compliant with standard format.

= Features =

**Select your e-commerce package**

	* Easy Digital Downloads or
	* Woo Commerce

**Create quarterly definitions**

	* Select the date range of the transactions to be included
	* Convert transactions recorded in another currency into the currency of your company
	* Generate a SAF file
	
**Videos**

	[Watch videos](http://www.wproute.com/standard-audit-file-saf-moss/ "Videos showing the plug-in working") showing how to configure the plug-in and create an audit file.

**Generate a Standard Audit File**

[Buy credits](http://www.wproute.com/standard-audit-file-saf-moss/ "Buy credits") to generate a standard audit file from your sales records.

== Frequently Asked Questions ==

= Q. Do I need to buy credits to use the plugin? =
A. No but you will need a credit to be able to generate the audit file.

== Installation ==

Install the plugin in the normal way then select the settings option option from the VAT MOSS menu added to the main WordPress menu.  Detailed [configuration and use instructions](http://www.wproute.com/standard-audit-file-saf-moss/) can be found on our web site.

**Requires**

This plugin requires that you capture VAT information in a supported format such as the format created by the [Lyquidity VAT plugin for EDD](http://www.wproute.com/ "VAT for EDD") or the [Woo Commerce EU VAT Compliance plugin](https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/ "Premium version").

== Screenshots ==

1. The first task is to define the settings that are common to all definitions.
2. The second task is to select the e-commerce package you are using.
3. The main screen shows a list of the existing definitions.
4. New definitions are created by specifying the correct header information, most of which is taken from the settings, and also select the sales transactions that should be includedin the submission

== Changelog ==

= 1.0 =
Initial version released

Fixed a problem with an invalid constant name in vatidvalidator.php

= 1.0.3 =

Extra protections against malicious execution
Small change to prevent js and css files being added to the front end

= 1.0.4 =

Fixed a problem selecting definitions by month

= 1.0.5 =

Added the ability to test an upload file generation.  The file will be created but the values will be zero

= 1.0.6 =

Added support for EU VAT Assistant for WooCommerce from Aelia
Added notices that VAT plugins must be installed and activated

= 1.0.7 =

Fixed the tests to confirm the existence of the Lyquidity plugin (EDD) or the Simba or EU VAT Assistant plugin (WooCommerce)

= 1.0.8 =

Updated references to the service site

= 1.0.9 =

Updated add_query_arg calls to escape them as recommended by the WordPress advisory

= 1.0.10 =

Fixed text domain errors

= 1.0.11 =

Fixed incompatibility with WP 4.4 that prevented the summary being displayed
Updates to prevent notice messages appear in the WP log when using PHP 7.0

= 1.0.12 =

Change the use of home_url( '/' ) to home_url( '/', $scheme = relative ) so the plugin will work on a site using HTTPS 
where the site blog table contains HTTP.

Supports WordPress 4.5.1

= 1.0.13 =

Update WordPress supported version

== Upgrade Notice ==

Nothing here
