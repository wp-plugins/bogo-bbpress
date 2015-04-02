=== Bogo bbPress ===
Contributors: mechter
Donate link: http://www.markusechterhoff.com/donation/
Tags: bogo, bbpress
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 3.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Multilingual bbPress with Bogo

== Description ==

Make your multilingual WordPress site have multilingual Forums, with Bogo and bbPress.

Say your default language bbPress is located at `/forums/` and you have German and Spanish translations of your website, then the localized bbPress versions can be viewed at `/de/forums/` and `/es/forums/` respectively.

All email notifications are localized to the language the user set in their profile. Also localizes URLs contained in emails where appropriate.

Development status: This plugin was developed because I thought it was a solution to my problem. It wasn't. Turns out what I really needed was a multisite setup with separate languages. When I found out, I was close the version 3.0 release of Bogo bbPress, so I finished up and released it. Please understand that I am busy with other projects now, do not rely on my support or maintenance of this plugin. If you fix a bug and send me a patch, I will gladly apply it. If you pay good money and I've got some time, I might fix or add something for you. Other than that, you're on your own.

== Installation ==

1. Install [Bogo](https://wordpress.org/plugins/bogo/), [bbPress](https://wordpress.org/plugins/bbpress/) and [BogoXLib](https://wordpress.org/plugins/bogoxlib/)
2. Install [bbPress language files](http://codex.bbpress.org/bbpress-in-your-language/)
3. Install this plugin

== Frequently Asked Questions ==

n/a

== Changelog ==

= 3.1 =

* updated to work with BogoXLib 1.1

= 3.0 =

* now using BogoXLib
* added email notification translation
* added automatic redirection to localized forum URLs for logged in users
* added support for Bogo language switcher (requires Bogo > 2.4.2 to work)

= 2.0 =

* added support for custom slugs

= 1.0 =

* initial release
