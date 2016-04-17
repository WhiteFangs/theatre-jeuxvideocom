<?php
include("Helpers.php");
$baseUrl = "http://www.jeuxvideo.com";

$acte = $_GET['acte'];
$pageCounter = 1;
for($i = 0; $i < $acte; $i++)
	$pageCounter += (mt_rand() / mt_getrandmax() < 0.5 ) ? 25 : 0;
$changeForum = (mt_rand() / mt_getrandmax() < 0.5 );
$forumUrl = $changeForum ? "/forums/0-50-0-1-0-" . $pageCounter . "-0-blabla-15-18-ans.htm" : "/forums/0-51-0-1-0-" . $pageCounter . "-0-blabla-18-25-ans.htm";
$forumUrl = $baseUrl . $forumUrl;
$topics = getTopics($forumUrl);
if(count($topics) > 0){
    $topic = $topics[array_rand($topics)];
    $posts = getPosts($topic->url);
    $posts = array_map("unserialize", array_unique(array_map("serialize", $posts)));
    $users = array_map(function($e) {return $e->user;}, $posts);
    $users = array_values(array_filter(array_unique($users)));
	$returnedPosts = array();
    foreach ($posts as $key => $post) {
		if(!empty($post->user)){
			$post->sentiments = getSentiments($post->text);
			$post->text = removeSmileys($post->text, true);
			$returnedPosts[] = $post;
		}
    }
	$arr = array('users' => $users, 'posts' => $returnedPosts, 'topic' => $topic);
	header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($arr);
}else{
	echo 'No topic found at ' . $forumUrl;
}

?>
