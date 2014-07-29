<?php

if(isset($_SERVER['QUERY_STRING'])) parse_str($_SERVER['QUERY_STRING'],$_GET);

$start_script_timer = microtime_float();

if(!function_exists('elapsed'))
  {
    function elapsed($start_time = null)
    {
      /***
       * Return the duration since the start time in
       * milliseconds.
       * If no start time is provided, it'll try to use the global
       * variable $start_script_timer
       *
       * @param float $start_time in unix epoch. See http://us1.php.net/microtime
       ***/

      if(!is_numeric($start_time))
        {
          global $start_script_timer;          
          if(is_numeric($start_script_timer)) $start_time = $start_script_timer;
          else return false;
        }      
      return 1000*(microtime_float() - (float)$start_time);
    }
  }

function returnAjax($data)
{
  if(!is_array($data)) $data=array($data);
  $data["execution_time"] = elapsed();
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
  header('Content-type: application/json');
  if (version_compare(PHP_VERSION,'5.3.0',">="))
    {
      # Safer
      print json_encode($data,JSON_FORCE_OBJECT);
    }
  else
    {
      print json_encode($data);
    }
  exit();
}

$url = url_decode($_GET['url']);
$raw = isset($_GET['raw']) ? $_GET['raw']:false;
$random = isset($_GET['random']) ? $_GET['random']:false;
$decode_entities = isset($_GET['decode_entities']) ? $_GET['decode_entities']:false;
$limit = is_int($_GET['limit']) ? $_GET['limit']:5;
$override_feed_title = isset($_GET['override_feed_title']) ? $_GET['override_feed_title']:false;
require_once(dirname(__FILE__)."/wp-embed-external-feed.php");

if($raw)
  {
    returnAjax(read_rss($url,$random,$limit));
  }
else
  {
    $html = embed_feed($url,$decode_entities,$limit,$random,$override_feed_title);
    returnAjax(array("status"=>true,"html"=>$html,"url"=>$url));
  }
