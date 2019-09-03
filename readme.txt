=== SCSS-Library ===

Contributors: sebaxtian  
Tags: SASS, compiler, SCSS  
Requires at least: 4.4  
Tested up to: 5.2.2  
Stable tag: trunk  
Requires PHP: 5.0  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add support for using SCSS style files with wp\_enqueue\_style.

== Description ==

This plugin allows you to use SCSS files directly in **wp\_enqueue\_style**. Just add the file to the list of styles and the plugin will compile it when necessary.

The base of this plugin is strongly influenced by the [WP-SCSS](https://wordpress.org/plugins/wp-scss/) code of and extracts some ideas from [Sassify](https://wordpress.org/plugins/sassify/). The goal is to keep the plugin updated with the latest version of [scssphp](https://packagist.org/packages/scssphp/scssphp), remove configuration options from the graphical interface, and use the **scssphp** capabilities to create debug files.

This plugin is not intended to be installed by a conventional user, but to be required by templates or plugins that wish to include **SCSS** style files and therefore the configuration is expected to be done in the code.

== Installation ==

1. Decompress scss-library.zip and upload `/scss-library/` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the __Plugins__ menu in WordPress.

== Frequently Asked Questions ==

= Performance =
This plugin adds many extra steps for something as simple as printing a style link tag inside a site:

* Check the creation time of the compiled file.
* Interacts with the database.
* Converts a SCSS file into a style file.

Obviously it will add a few thousandths of a second to the loading time of the site.

= How much will performance be affected? =
It depends on how many **SCSS** files you add to the list of styles and how complex they are.

= So I shouldn't use it in production? =
Of course you can use it. If you are looking for a fast site then you should also add a cache or optimization plugin to your production environment, although it is very likely that you have already done so. Personally I have worked with [Comet Cache](https://wordpress.org/plugins/comet-cache/) and [Autoptimize](https://wordpress.org/plugins/autoptimize/) without any inconvenience. Any problems you encounter with another cache plugin don't hesitate to write down the details to replicate the error. Remember that the more information you include in the report the easier it will be to fix it.

= Then what are you looking for with this plugin? =
What I want is to emulate for the style files the ease of development offered by [Timber](https://wordpress.org/plugins/timber-library/). Let **SCSS-Library** be to **SCSS** what **Timber** is to **Twig**.

My goal with this plugin is to be able to change the SCSS file directly and see the result immediately. No previous compilations or commands in a terminal. It is intended for development teams that include graphic designers who understand **CSS** and **HTML** but prefer not to have to open a terminal; and to support lazy programmers who like me prefer to leave repetitive tasks to the machines.

= Is this plugin bug free? =
I don't think so. Feedbacks would be appreciated.

== Changelog ==
= 0.1.2 =
* Fixing filename bugs.
* A new version number in the declaration sets a new filename, which creates new files without deleting the previous ones. Now the plugin uses only the url path as the basis for the name of the compiled file.
* Create compiled file if the file does not exist.

= 0.1.1 =
* Solving multisite bug.

= 0.1 =
* First release.
