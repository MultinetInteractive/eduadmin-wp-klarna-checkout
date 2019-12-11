=== EduAdmin - Klarna Checkout WordPress-plugin ===
Contributors: mnchga
Tags: booking, participants, courses, events, eduadmin, lega online, klarna
Requires at least: 4.7
Tested up to: 5.3
Stable tag: 1.1.1
Requires PHP: 5.2
License: GPL3
License-URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Plugin to the EduAdmin-Booking plugin to enable Klarna payments

== Description == 

# EduAdmin - Klarna Checkout WordPress-plugin

Plugin to enable payment via Klarna Checkout in the [EduAdmin-Wordpress plugin](https://github.com/MultinetInteractive/EduAdmin-WordPress)

[<img src="https://img.shields.io/wordpress/plugin/v/eduadmin-booking-klarna-checkout.svg" alt="Plugin version" />](https://wordpress.org/plugins/eduadmin-booking-klarna-checkout/)
[<img src="https://img.shields.io/wordpress/plugin/dt/eduadmin-booking-klarna-checkout.svg" alt="Downloads" />](https://wordpress.org/plugins/eduadmin-booking-klarna-checkout/)
[<img src="https://img.shields.io/wordpress/v/eduadmin-booking-klarna-checkout.svg" alt="Tested up to" />](https://wordpress.org/plugins/eduadmin-booking-klarna-checkout/)
[<img src="https://img.shields.io/github/commits-since/MultinetInteractive/eduadmin-wp-klarna-checkout/latest.svg" alt="Plugin version" />](https://wordpress.org/plugins/eduadmin-booking-klarna-checkout/)


Stats

[![Build Status](https://scrutinizer-ci.com/g/MultinetInteractive/eduadmin-wp-klarna-checkout/badges/build.png?b=master)](https://scrutinizer-ci.com/g/MultinetInteractive/eduadmin-wp-klarna-checkout/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/MultinetInteractive/eduadmin-wp-klarna-checkout/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/MultinetInteractive/eduadmin-wp-klarna-checkout/?branch=master)

== Changelog ==

### 1.1.1
- feat: Added `type` to plugin info, so [EduAdmin-Wordpress](https://github.com/MultinetInteractive/EduAdmin-WordPress) will know it's a payment plugin

### 1.1.0
- feat: Added event location (If applicable) and start/enddate (If applicable) to order rows

### 1.0.5
- add: Set `BookingId` in `merchant_reference1` for tracing ability between Klarna and EduAdmin.

### 1.0.4
- fix: Fix in the deploy script.

### 1.0.3
- chg: Changing paymentmethod to CardPayment regardless (for EduAdmin to catch overdue payments)

### 1.0.2
- add: Support for programme bookings

### 1.0.1
- add: Update order in EduAdmin

### 1.0.0
Initial release

== Installation ==

- Upload the zip-file (or install from WordPress) and activate the plugin
- Provide the API key from EduAdmin.
- Create pages for the different views and give them their shortcodes
