wp-embed-external-feed
======================

Wordpress plugin to embed external feeds in your site

## Usage

Please note the feeds are **unstyled**, but clearly marked up. You'll have to style the feed yourself, but much of the default styles of your theme should come along.

### Direct PHP

**Note**: This will slow your page load time

1. Use a plugin such as [Insert PHP](http://www.willmaster.com/software/WPplugins/) to allow PHP to run directly in your code
2. Add `[insert_php]echo embed_feed("FEED_URL_HERE");[/insert_php]` to place the feed in that location
