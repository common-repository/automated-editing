=== Automated Editing ===

Contributors: tepelstreel
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7RG539AT3TDMA
Tags: auto excerpt, automated excerpt, automated editing, auto excerpt, automation, automatic repair, network repair, network enabled, multisite, multisite fix, fix posts
Requires at least: 3.1
Tested up to: 4.5
Stable tag: 2.0.1

If working with a lot of editors, who either don't know how to use WP or don't pay attention, this plugin helps you by adding an excerpt automatically to every post, that doesn't have one. Since version 1.2 of the plugin it requires at least WP version 3.1, because it will also add a thumbnail, if the post doesn't have one. Therefore, the function set_post_thumbnail is used, which only exists from on WP version 3.1.

== Description ==

The Automated Editing was made, because there are so many people, who just forget to add an excerpt or tags to the post, when they publish it. For several reasons that can be annoying. So, every time a post is saved, the plugin just checks, whether there is an excerpt or not. If there is no excerpt, it will check, whether you have defined some length or type of excerpt. If you haven't, the plugin just creates a post excerpt by cutting off all short codes of the content and taking the first 3 sentences as the excerpt. The same happens with the 'more' tag. It will be automatically set, if you want it. If you don't define a number of sentences after which the tag has to be inserted, the plugin will insert it after 5 sentences.

Since version 1.4 you have the possibility to check elder posts for missing excerpts, 'more' tags and thumbnails. The plugin will try to fix those posts automatically and deliver a list of posts that could not be fixed. You can access these posts from the admin panel of the automated editing plugin and try to fix them manually.

It is also completely multisite enabled since version 1.4. You can check and repair posts network-wide.

== Installation ==

1. Upload the `automated-editing` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Define length and type of excerpt on the options page
4. Ready

== Changelog ==

= 2.0.1 =

* Debug options added to framework
* Some bugs fixed

= 2.0 =

* WP 4.5 ready
* Framework updated
* Default image for thumbnail added

= 1.9.2 =

* WP 4.1 ready
* Framework updated
* Bug with setting unwanted thumbnails fixed

= 1.9.1 =

* German translation updated

= 1.9 =

* enhanced functionality
* hooks now in the yoast seo plugin

= 1.8.2 =

* Bugfix

= 1.8.1 =

* Adjustment for new framework

= 1.8 =

* One more bug fixed
* Framework adjusted
* Ability to add images that are in the post content, but not attached as thumbnail

= 1.7 =

* Loads of bugs fixed
* Framework changed
* More functions and structure for custom post types added

= 1.6 =

* Framework streamlined
* Bugfix

= 1.5 =

* Framework streamlined
* Small bugfix

= 1.4 =

* Ability to check old posts for missing excerpts, 'more' tags and thumbnails
* Ability to fix old posts with missing excerpts, 'more' tags and thumbnails automatically
* Unfixable posts are stored in a list for manual correction
* Completely multisite enabled; posts can be checked and repaired network wide

= 1.3 =

* Complete overhaul of the code

= 1.2 =

* A post thumbnail is also put now
* You can choose also an amount of letters as auto-excerpt

= 1.1 =

* German translation added; one typo fixed

= 1.0 =

* Initial Release

= 0.9 beta =

* Basic plugin for personal use

== Upgrade Notice ==

= 2.0.1 =

Debug options added to framework; some bugs fixed

= 2.0 =

WP 4.5 ready; framework updated; default image for thumbnail added

= 1.9.2 =

WP 4.1 ready; framework updated; bug with setting unwanted thumbnails fixed

= 1.9.1 =

German translation updated

= 1.9 =

enhanced functionality; hooks now in the yeast sep plugin

= 1.8.2 =

Bugfix

= 1.8.1 =

Adjustment for new framework

= 1.8 =

One more bug fixed. Framework adjusted. Ability to add images that are in the post content, but not attached as thumbnail

= 1.7 =

More functions and structure for custom post types added, loads of bugs fixed and framework changed

= 1.6 =

Framework streamlined; bugfix

= 1.5 =

Framework streamlined; small bugfix

= 1.4 =

Network enabled and enhenced

= 1.3 =

Complete overhaul of the code

= 1.2 =

Post thumbnail is added automatically now and you can finetune your excerpt a little more

= 1.1 =

German translation added; one typo fixed

= 1.0 =

Initial Release