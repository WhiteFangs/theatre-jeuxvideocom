<?php
include("Helpers.php");

$baseUrl = "http://www.jeuxvideo.com";
$forumUrl = (mt_rand() / mt_getrandmax() < 0.5 ) ? "/forums/0-50-0-1-0-1-0-blabla-15-18-ans.htm" : "/forums/0-51-0-1-0-1-0-blabla-18-25-ans.htm";
$forumUrl = $baseUrl . $forumUrl;

$topics = getTopics($forumUrl);
if(count($topics) > 0){
  $topic = $topics[array_rand($topics)];
  echo $topic->name;
  echo '<br>';
  echo $topic->url;
  echo '<br>';
  $posts = getPosts($topic->url);
  var_dump($posts);
}

?>
