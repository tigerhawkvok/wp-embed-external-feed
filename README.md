wp-embed-external-feed
======================

Wordpress plugin to embed external feeds in your site

## Usage

Please note the feeds are **unstyled**, but clearly marked up. You'll
have to style the feed yourself, but much of the default styles of
your theme should come along.

### Direct PHP

**Note**: This will slow your page load time!

1. Use a plugin such as
   [Insert PHP](http://www.willmaster.com/software/WPplugins/) to
   allow PHP to run directly in your code
2. Add `[insert_php]echo embed_feed("FEED_URL_HERE");[/insert_php]` to
   place the feed in that location

### Aysnchronous

1. Declare your list of feeds, much like this:

   ```php
   $feeds_list = array(
     array(
       "url"=>"http://www.feedurl.com/feedpath",
       "limit"=>3
     ),
    ...
   );
   ```

  (see the documentation for the function [`embed_feed()`](wp-embed-external-feed.php) for more details)
2. Call `load_feeds_async($feeds_list)`. Add the parameter `true` if this is to be called after scripts have been enqueued.
