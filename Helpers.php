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
  $page = preg_replace("~<blockquote(.*?)>(.*)</blockquote>~si","", $page);
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
    if(strstr($topicPage, ($pageCount + 1 ) . "-0-1-0") != false && strstr($topicPage, "Page suivante") != false){
      $topicUrl = str_replace(($pageCount) . "-0-1-0", ($pageCount + 1 ) . "-0-1-0", $topicUrl);
      $pageCount++;
    }
    else{
      $topicUrl = "";
    }
  }
  return $posts;
}

function getSentiments($text){
  $sentiments = array();
  $otherSentiments = array();
  if(strstr($text, ":snif2:") != false) $sentiments[] = "pleurant beaucoup";
  if(strstr($text, ":rire2:") != false) $sentiments[] = "riant avec malice";
  $text = preg_replace("/:([^\s:]*?)\d+:/", ":$1:", $text);
  if(strstr($text, ":)") != false) $sentiments[] = "souriant";
  if(strstr($text, ":snif:") != false) $sentiments[] = "pleurant";
  if(strstr($text, ":-)") != false) $sentiments[] = "très souriant";
  if(strstr($text, ":hap:") != false) $sentiments[] = "hapiste";
  if(strstr($text, ":noel:") != false) $sentiments[] = "noeliste";
  if(strstr($text, ":(") != false) $sentiments[] = "triste";
  if(strstr($text, ":-(") != false) $sentiments[] = "très triste";
  if(strstr($text, ":cool:") != false) $sentiments[] = "cool";
  if(strstr($text, ":rire:") != false) $sentiments[] = "riant";
  if(strstr($text, ":ok:") != false) $sentiments[] = "d'accord";
  if(strstr($text, ":ouch:") != false) $sentiments[] = "éberlué";
  if(strstr($text, ":doute:") != false) $sentiments[] = "doutant";
  if(strstr($text, ":oui:") != false) $sentiments[] = "acquiesçant";
  if(strstr($text, ":d)") != false) $sentiments[] = "montrant vers la droite";
  if(strstr($text, ":g)") != false) $sentiments[] = "montrant vers la gauche";
  if(strstr($text, ":bave:") != false) $sentiments[] = "bavant";
  if(strstr($text, ":o))") != false) $sentiments[] = "clownesque";
  if(strstr($text, ":coeur:") != false) $sentiments[] = "amoureux";
  if(strstr($text, ":up:") != false) $sentiments[] = "montrant vers le haut";
  if(strstr($text, ":lol:") != false) $sentiments[] = "riant à gorge déployée";
  if(strstr($text, ":question:") != false) $sentiments[] = "s'interrogeant";
  if(strstr($text, ":honte:") != false) $sentiments[] = "honteux";
  if(strstr($text, ":fete:") != false) $sentiments[] = "célébrant";
  if(strstr($text, ":-p") != false) $sentiments[] = "tirant allégrement la langue";
  if(strstr($text, ":p)") != false) $sentiments[] = "tirant la langue";
  if(strstr($text, ":peur:") != false) $sentiments[] = "peureux";
  if(strstr($text, ":hum:") != false) $sentiments[] = "embarrassé";
  if(strstr($text, ":content:") != false) $sentiments[] = "content";
  if(strstr($text, ":malade:") != false) $sentiments[] = "nauséeux";
  if(strstr($text, ":pf:") != false) $sentiments[] = "désabusé";
  $text = removeSmileys($text, false);
  preg_match_all("/:([^\s:]*?):/", $text, $otherSentiments);
  foreach ($otherSentiments[1] as $key => $sent) {
    $sent = preg_replace('/\d/', "", $sent);
    $sentiments[] = $sent;
  }
  $sentiments = array_unique($sentiments);
  return $sentiments;
}

function removeSmileys($text, $removeAll){
  $text = str_replace(":)", "", $text);
  $text = str_replace(":snif:", "", $text);
  $text = str_replace(":-)", "", $text);
  $text = str_replace(":snif2:", "", $text);
  $text = str_replace(":hap:", "", $text);
  $text = str_replace(":noel:", "", $text);
  $text = str_replace(":(", "", $text);
  $text = str_replace(":-(", "", $text);
  $text = str_replace(":cool:", "", $text);
  $text = str_replace(":rire:", "", $text);
  $text = str_replace(":ok:", "", $text);
  $text = str_replace(":ouch:", "", $text);
  $text = str_replace(":doute:", "", $text);
  $text = str_replace(":oui:", "", $text);
  $text = str_replace(":d)", "", $text);
  $text = str_replace(":g)", "", $text);
  $text = str_replace(":bave:", "", $text);
  $text = str_replace(":o))", "", $text);
  $text = str_replace(":coeur:", "", $text);
  $text = str_replace(":up:", "", $text);
  $text = str_replace(":lol:", "", $text);
  $text = str_replace(":question:", "", $text);
  $text = str_replace(":honte:", "", $text);
  $text = str_replace(":fete:", "", $text);
  $text = str_replace(":-p", "", $text);
  $text = str_replace(":p)", "", $text);
  $text = str_replace(":peur:", "", $text);
  $text = str_replace(":hum:", "", $text);
  $text = str_replace(":content:", "", $text);
  $text = str_replace(":malade:", "", $text);
  $text = str_replace(":rire2:", "", $text);
  $text = str_replace(":pf:", "", $text);
  if($removeAll)
    $text = preg_replace("/:([^\s:]*?):/", "", $text);
  return $text;
}

?>
