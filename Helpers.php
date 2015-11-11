<?php

function getCURLOutput($url){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_AUTOREFERER, true);
  curl_setopt($ch, CURLOPT_VERBOSE, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
  $output = curl_exec($ch);
  curl_close($ch);
  return $output;
}

function getDOMXPath($page){
  $dom = new DOMDocument;
  $page = mb_convert_encoding($page, 'HTML-ENTITIES', "UTF-8");
  @$dom->loadHTML($page);
  $xpath = new DOMXPath($dom);
  return $xpath;
}

function getTopics ($forumUrl) {
  $baseUrl = "http://www.jeuxvideo.com";
  $topics = array();
  $forumPage = getCURLOutput($forumUrl);
  $forumXpath = getDOMXPath($forumPage);
  $topicsName = $forumXpath->query('//*[@class="titre-topic"]/a/text()');
  $topicsNodes = $forumXpath->query('//*[@class="titre-topic"]/a');
  foreach($topicsNodes as $key => $node) {
    if($key > 4){ // first 4 topics are often pinned
      $topic = (object) array('name' => $topicsName->item($key)->nodeValue, 'url' => $baseUrl . $node->getAttribute("href"));
      $topics[] = $topic;
    }
  }
  return $topics;
}

function cleanHTML($page){
  $matchImg = array();
  $page = str_replace("<p>", "", $page);
  $page = str_replace("</p>", "", $page);
  $page = str_replace("<br>", ":saut de ligne:", $page);
  preg_match_all("/<img[^>]+\>/i", $page, $matchImg);
  $matchImg = array_unique($matchImg[0]);
  foreach ($matchImg as $key => $img) {
    if(strpos($img, 'data-def="SMILEYS"') != false){
      $codeImg = $img;
      $codeImg = preg_replace('/<img[^>]+data-code=\"/i', "", $codeImg);
      $codeImg = preg_replace('/\" title.*/i', "", $codeImg);
      $page = str_replace($img, $codeImg, $page);
    }
  }
  $page = preg_replace("/<img[^>]+\>/i", "", $page);
  return $page;
}

function getPosts($topicUrl){
  $posts = array();
  $pageCount = 0;
  while($topicUrl != "" && $pageCount < 5){
    $topicPage = getCURLOutput($topicUrl);
    $topicPage = cleanHTML($topicPage);
    $topicXpath = getDOMXPath($topicPage);
    $textNodes = $topicXpath->query('//*[contains(@class, "txt-msg") and contains(@class ,"text-enrichi-forum")]');
    $userNodes = $topicXpath->query('//*[contains(@class, "bloc-pseudo-msg") and contains(@class, "text-user")]/text()');
    for($i = 0; $i < $textNodes->length; $i++){
      $post = (object) array('user' => $userNodes->item($i)->nodeValue, 'text' => $textNodes->item($i)->nodeValue);
      $posts[] = $post;
    }
    $nextPage = $topicXpath->query('//*[@class="pagi-after"]');
    if($nextPage->length > 0){
      $pageCount++;
      $topicUrl = str_replace(($pageCount-1) . "-0-1-0", $pageCount . "-0-1-0", $topicUrl);
    }
    else{
      $topicUrl = "";
    }
  }
  return $posts;
}

?>
