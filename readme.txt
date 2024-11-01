=== wpTwitBox ===
Contributors: stalkerX
Tags: twitter, bitly, tweet, retweet, oauth
Requires at least: 2.8
Tested up to: 3.3
Stable tag: trunk

Tool box with useful power tools around Twitter and Bit.ly shortener.


== Description ==
= Twitter on WordPress =
A collection of useful features designed for Twitter users. Most Twitter and Bit.ly features in one plugin.


= Features =
* Twitter OAuth authentication
* Daily update for your twitter followers count
* Theme template: Display your twitter followers count via hook
* Auto Tweet: Twitter notification if new posts
* Convert links to Bit.ly links in incoming comments
* Keep post permalinks as cachable Bit.ly links
* Generate a "Twit This" link
* Option: Use J.mp instead of Bit.ly
* WordPress 3.0.x ready: Design as well as technical
* Clean up after uninstall the plugin

= Template Output: Followers Count =
`<?php do_action('wptwitbox_followers_count'); ?>`
Very simple.

= Template Output: Twit This Link =
`<?php do_action('wptwitbox_tweet_link'); ?>`
Very clear.


= Misc =
* [Plugin documentation in german](http://playground.ebiene.de/2375/wptwitbox-wordpress-plugin/ "wpTwitBox")
* [Follow us on Twitter for updates](http://twitter.com/wpSEO "wpSEO on Twitter")
* [Other author plugins](http://wordpress.org/extend/plugins/profile/stalkerx "Other author plugins")
* [Author page](http://ebiene.de "Author page")


== Changelog ==
= 0.5.1 =
* Rename login/index.php to login/login.php
* Remove break in the Bit.ly link
* Follower count: Reduce the time interval to 1 hour
* Auto tweets: Correction for scheduled Posts

= 0.5 =
* **Important changes, please check plugin settings**
* Twitter OAuth authentication (no password needed)
* Bit.ly authentication with login and API key
* Bit.ly access inline verification
* Bit.ly API Version 3
* Code and GUI restructuring
* WordPress 3.0 support

= 0.4 =
* Important changes for shutdown of Bit.ly API parts 

= 0.3 =
* Ignore links in spam comments
* Add hook support for the attaching action
* Small bug fixes and optimizations

= 0.2 =
* Add new plugin icon
* Fix for publish_future_post action

= 0.1 =
* wpTwitBox goes online


== Screenshots ==

1. wpTwitBox settings


== Installation ==
1. Download the *wpTwitBox* plugin
1. Unzip the archive into *wptwitbox*
1. Upload the folder *wptwitbox* into *../wp-content/plugins/*
1. Go to tab *Plugins*
1. Activate *wpTwitBox*
1. Edit settings
1. Ready