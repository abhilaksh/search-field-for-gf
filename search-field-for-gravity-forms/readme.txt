=== Search Field for Gravity Forms ===
Contributors: wpsunshine
Tags: gravityforms, gravity forms, search
Requires at least: 5.0
Tested up to: 6.2
Requires PHP: 5.6
Stable tag: 1.1.1
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html

Searches selected post types after a user types, displaying results below field.

== Description ==

A custom field for Gravity Forms that will search any selected post types as the user types into the field and show the results below the field. Set which post types, how many results, and customize the display format of the results. Lots of unique CSS classes included for full custom styling!

This plugin was created as a way to help customers find documentation articles before submitting a support ticket. The goal is to reduce the number of support tickets submitted by helping customers find and answer themselves. Maybe you can find other uses as well!

[Documentation](https://www.wpsunshine.com/plugins/gravity-forms-search-field/?utm_source=wordpress.org&utm_medium=link&utm_campaign=gravityforms-search-readme)


== Installation ==

You must have Gravity Forms installed first!

1. Upload and activate the plugin
2. Find the new "Search" field when adding/editing a form and add it to your form
3. Customize the field settings

== Screenshots ==

1. Field settings
2. Sample form with search field showing results

== Changelog ==

= 1.1.1 =
* Fix - Meta data now works on more than 1 field per result

= 1.1 =
* Add - Now results can show metadata

= 1.0 =
* Ready for full 1.0 status!

= 0.7.1 =
* Fix - Change how 'gravityforms_search_no_results' gets sanitized before output to allow HTML

= 0.7 =
* Add - filter 'gravityforms_search_no_results' to customize the "No results" text

= 0.6.1 =
* Change - Only check for typing on 'keyup', not 'change' as it resulted in redoing search on click outside input

= 0.6 =
* Update - Better loading spinner that shows inside the form field

= 0.5 =
* Fix - Fix issue getting post types

= 0.4 =
* Add - Use esc_* functions more liberally

= 0.3 =
* Fix - Search settings were showing on Text fields as well
* Fix - Proper Text Domain

= 0.2 =
* Fix - Add sanitization where missing

= 0.1 =
* Initial release
