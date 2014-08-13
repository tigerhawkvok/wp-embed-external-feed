wp-embed-external-feed
======================

Wordpress plugin to embed external feeds in your site

## License

You may either use the MIT or GPLv3 license, as is appropriate to the licensing level of your work.
If your work is closed source, you may use MIT; if it is open source, you must use GPLv3.
Both are available in this repository, as LICENSE and LICENSE-2, respectively.

### YUICompressor

The file `yuicompressor.jar` is from the [YUICompressor project](https://github.com/yui/yuicompressor), with the same BSD license.

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
2. Call `load_feeds_async($feeds_list)`. Add the parameter `true` if this is to be called after scripts have been enqueued. You can further add the element ID after which to insert the feeds if not using the default `before_feeds` as your reference ID. See the function documentation for more details.
   - Alternately, give an element the id `before_feeds` (or edit the default, or add the new element ID as an argument in the onload in `coffee/wp_embed_external_feed_load_async.coffee`)

**Note**: Be sure you do your edits in HTML mode. The `>` is changed to `&gt;` in WYSIWYG mode, and this will cause your feeds to fail.
