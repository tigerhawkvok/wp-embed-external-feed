<?php
/***
 * Plugin Name: wp-embed-external-feed
 * Plugin URI: https://github.com/tigerhawkvok/wp-embed-external-feed
 * Description: Include external feeds in your Wordpress page
 * Author: Philip Kahn
 * Author URI:
 * Version: 1.0.0
 * License: MIT
 ***/

/*************************************
 * Core feed insertion functions
 **************************************/

function read_rss($url=null,$random=false,$limit=5)
{
  /***
   * For a given URL, return an array of feed data
   *
   * @param string $url the URL of the feed
   * @param boolean $random whether to shuffle the feed items before
   * returning (default false)
   * @param int $limit how many feed items to limit the return to
   * (default 5)
   * @return false|array with the feed name in "feed_name", link in
   * "feed_link", and post data in keys represented by the Unix posting
   * time, containing the post link, title, date, time, and description
   ***/
  
  if($url==null) $url='http://newscenter.berkeley.edu/feed/';
  try
    {
      # Preferred as per PHP doc
      $data = file_get_contents($url);
    }
  catch(Exception $e)
    {
      # Compatibility
      $ch = curl_init("$url");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $data = curl_exec($ch);
      curl_close($ch);
    }
  if (empty($data))
    {
      # Compatibility
      $ch = curl_init("$url");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $data = curl_exec($ch);
      curl_close($ch);
    }
  if(strpos($data,"<?xml")!==false)
    {
      try
        {
          $cdata=array("//<![CDATA[","//]]>","<![CDATA[","]]>");
          $item_list=explode("<item>",$data);
          /*
           * Places like blogger sometimes use <entry> instead of
           * <item>. Since we'll only use things with at least one
           * post, we look for at least one exploded element, eg, a
           * 2-item array
           */
          if(sizeof($item_list) < 2 ) 
            {
              // Try an alternate
              $item_list = explode("<entry>",$data);
              $using_alt = true;
            }
          else $using_alt = false;
          if(sizeof($item_list) < 2 ) return false;
          $container=array();
          $container['using_alt'] = $using_alt;
          $container['feed_count'] = sizeof($item_list);
          $container['raw_item_list'] = $item_list;
          $iter=0;
          foreach($item_list as $k=>$item)
            {
              if (!$using_alt)
                {
                  $link = getTagContents($item,"<link>");
                }
              else
                {
                  $link_arr = getTagAttributes($item,"<link>",true);
                  foreach($link_arr as $link_test)
                    {
                      if($link_test['rel'] == 'alternate')
                        {
                          $link = $link_test['href'];
                          break;
                        }
                    }
                }
              if($k==0) {
                $container['feed_name']=getTagContents($item,"<title>");
                $container['feed_link'] = $link;
              }
              else
                {
                  $permalink = $link;
                  $title=getTagContents($item,"<title>");
                  $content_label = $using_alt ? "<content>":"<description>";
                  $desc=str_replace($cdata,'',getTagContents($item,$content_label));
                  $date_label = $using_alt ? "<published>":"<pubDate>";
                  $date=getTagContents($item,$date_label);
                  $time=strtotime($date);
                  $container[$time]=array('link'=>$permalink,'title'=>$title,'description'=>$desc,'date'=>$date,'time'=>$time);
                  $iter++;
                }
              if($iter>=$limit) break;
            }
          if(sizeof($container)<=1) return false;
          if($random===false) return $container;
          else
            {
              if(is_numeric($random))
                {
                  try
                    {
                      $temp=array_reverse($container);
                      $title=array_pop($temp);
                      shuffle($temp);
                      $container=array_slice($temp,0,$random);
                      $container['feed_name']=$title;
                      return $container;
                    }
                  catch(Exception $e)
                    {
                      return false;
                    }
                }
              if($random===true) return shuffle($container);
            }
        }
      catch(Exception $e)
        {
          return false;
        }
    }
  else return false;

}

function embed_feed($url=null,$decode_entities = false,$limit=5,$random=false,$override_feed_title = false)
{
  /***
   * Return an HTML formatted feed blob.
   *
   * @param string $url
   * @param boolean $decode_entities decodes the URL encoded entities
   * in the page. Some feeds will encode the HTML in a post, so this
   * will decode them to have the HTML correctly rendered. Notes this
   * uses htmlspecialchars, so most numerical encodings should be
   * safe. (default false)
   * @param int $limit the number of posts to show (default 5)
   * @param boolean $random Should the post order be shuffled?
   * (default false)
   * @param string $override_feed_title If the feed has a title that
   * doesn't make sense in context, you can override it here.
   * @return string An HTML string of the feed
   ***/
  if(!is_numeric($limit)) return false;
  $data=read_rss($url,$random);

  if($data!==false)
    {
      $feed_title = $override_feed_title !== false ? $override_feed_title:$data['feed_name'];
      $buffer="<section class='feed_block'><h1 class='embedded_feed'><a href='".$data['feed_link']."' onclick='window.open(this.href); return false;' onkeypress='window.open(this.href); return false;'>".$feed_title."</a></h1>";
      $iter=0;
      foreach($data as $k=>$item)
        {
          if(is_numeric($k))
            {
              $buffer.=format_feed_item($item,$decode_entities);
              $iter++;
            }
          if($iter>=$limit) break;
        }
      $buffer.="</section>";
      return $buffer;
    }
  return false;
}

function format_feed_item($item,$decode_entities = false)
{
  /***
   * Formats the feed items to HTML, specifically geared for Wordpress
   *
   * @param array $item a feed item as returned by read_rss()
   * @param boolean $decode_entities Decode HTML special
   * characters. Use this if the feed encodes its HTML, showing raw
   * tags in the output. (default false)
   * @return string An XHTML5 formatted post
   ***/
  try
    {
      $newwindow="onclick='window.open(this.href); return false;' onkeypress='window.open(this.href); return false;'";
      $description = $decode_entities ? htmlspecialchars_decode($item['description']):$item['description'];
      $description = preg_replace('/font-(family|size):.*?;/', '', $description);
      return "\n\t<article class='feed_item post type-post status-publish format-standard hentry category-first-category'>\n\t\t<header class='feed_item feed_item_header entry-header'>\n\t\t\t<h1 class='entry-title'><a href='".$item['link']."' $newwindow>".$item['title']."</a></h1>\n\t\t</header>\n\t\t<section class='feed_item feed_item_description'><p>".$description."</p><p class='feed_link'><a href='".$item['link']."' class='button-primary' $newwindow>Read more &#187;</a></p>\n\t\t</section>\n\t\t<footer class='entry-meta'><time datetime='".$item['date']."' pubDate='pubDate' class='relative_time'>".relative_time($item['time'])."</time></footer>\n\t</article>";
    }
  catch (Exception $e)
    {
      return false;
    }
}



function relative_time($time, $postfix = ' ago', $fallback = 'F Y') 
{
  /***
   * Returns a pretty time.
   *
   * @param int|float $time a unix-timestamp compatible time
   * @param string $postfix After the relative time. (Default: "ago")
   * @param string $fallback If it can't be parsed, what format to
   * return the pretty time in. See the documentation here:
   * http://us3.php.net/manual/en/function.date.php#refsect1-function.date-parameters 
   ***/
  $diff = time() - $time;
  if($diff < 60) 
    return $diff . ' second'. ($diff != 1 ? 's' : '') . $postfix;
  $diff = round($diff/60);
  if($diff < 60) 
    return $diff . ' minute'. ($diff != 1 ? 's' : '') . $postfix;
  $diff = round($diff/60);
  if($diff < 24) 
    return $diff . ' hour'. ($diff != 1 ? 's' : '') . $postfix;
  $diff = round($diff/24);
  if($diff < 7) 
    return $diff . ' day'. ($diff != 1 ? 's' : '') . $postfix;
  $diff = round($diff/7);
  if($diff < 4) 
    return $diff . ' week'. ($diff != 1 ? 's' : '') . $postfix;
  $diff = round($diff/4);
  if($diff < 12) 
    return $diff . ' month'. ($diff != 1 ? 's' : '') . $postfix;

  return date($fallback, strtotime($date));
}

function getTagContents($string,$tag)
{
  /***
   * Get the contents of the first instance of a given tag
   *
   * @param string $string the blob to search through
   * @param string $tag the tag to look for
   * @return false|string the contents of <tag>
   ***/
  if(strpos($tag,"<")===false) $tag = "<".$tag;
  if(strpos($tag,">")===false) $tag .= ">";
  $pos=strpos($string,substr($tag,0,-1));
  if($pos!==false)
    {
      $val=substr($string,$pos+strlen(substr($tag,0,-1)));
      $pos = strpos($val,">"); # find the next occurance
      $val = substr($val,$pos+1);
      $val=explode("</" . str_replace(array("<",">"),"",$tag),$val);
      return $val[0]; # always the first one
    }
  else return false;
}




function getTagAttributes($string,$tag,$all=false)
{
  /***
   * Return a array(attribute=>value) list of attributes for a tag
   * type
   *
   * @param string $string the text blob to search through
   * @param string $tag the tag to look for
   * @param boolean Should all instances of <tag> in $string be looked
   * for? (default: false)
   * @return array of [attribute=>value] pairs. If $all is true, then
   * each instance of <tag> has its attributes returned in a numeric
   * key as array(0 => array(attribute=>value), 1 => ...)
   ***/
  $tag=str_replace("<","",$tag);
  $tag=str_replace(">","",$tag);
  $tag="<".$tag;
  $pos=strpos($string,$tag);
  if($pos!==false)
    {
      # found a valid tag
      # return all attribute values for a given tag
      $all_tags=explode($tag,$string);
      # it should never be the first iterator. Kill it.
      $all_tags=array_slice($all_tags,1);
      if($all) $parent_array=array();
      foreach($all_tags as $sstring)
        {
          $pos2=strpos($sstring,">");
          $sstring=substr($sstring,0,$pos2);
          if(empty($sstring) && !$all) return false; # this means that the tag has no attributes
          if(!empty($sstring))
            {
              $attributes=preg_split("/[\"'] +/",$sstring);
              # iterate through $attributes, and break each attribute pair into a subarray
              $result_array=array();
              foreach($attributes as $attribute)
                {
                  $pair=explode("=",$attribute);
                  $i=0;
                  foreach($pair as $value) 
                    {
                      # remove leading or trailing quote
                      $value=str_replace('"',"",$value);
                      $value=str_replace("'","",$value);
                      $value=str_replace("&#39;","",$value);
                      $pair[$i]=trim($value);
                      $i++;
                    }
                  $result_array[$pair[0]]=$pair[1];
                }
              if(!$all) return $result_array;
            }
          # This means $all has been declared
          # stuff into larger parent array
          if(empty($sstring)) $parent_array[]=false;
          else $parent_array[]=$result_array;
        }
      # take large parent array and return that
      return $parent_array;
    }
  return false;
}

function isURL($url)
{
  /***
   * Helper function to test URL validity
   * @param string $url
   * @return boolean
   ***/
  return preg_match("@(https?|ftp)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$@iS",$url);
}

function curPageURL()
{
  /***
   * Helper function. Get the current page's URL
   *
   * @return string the current page's URL
   ***/
  $pageURL = 'http';
  if ($_SERVER["HTTPS"] == "on")
    {
      $pageURL .= "s";
    }
  $pageURL .= "://";
  if ($_SERVER["SERVER_PORT"] != "80")
    {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    }
  else
    {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
  return $pageURL;
}

function shuffle_assoc(&$array)
{
  /***
   * Helper function. Shuffle an associative array, maintaining key
   * associations. This is done in-place.
   ***/
  $keys = array_keys($array);
  shuffle($keys);
  foreach($keys as $key)
    {
      $new[$key] = $array[$key];
    }
  $array = $new;
  return true;
}


?>