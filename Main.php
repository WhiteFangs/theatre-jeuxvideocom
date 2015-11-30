<?php
include("Helpers.php");
$baseUrl = "http://www.jeuxvideo.com";
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Le jeuxvideo.com de l'amour et du hasard</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Tangerine" />
        <style>
        *{
          text-align: center;
          text-align: justify;
        }

        #content{
          margin-left: auto;
          margin-right: auto;
          max-width: 800px;
        }

        h1, h2, h3{
          font-family: 'Tangerine', Georgia;
          margin-left: auto;
          margin-right: auto;
          max-width: 800px;
          text-align: center;
          font-size: 3em;
        }

        h3 {
            font-size: 2em;
        }

        .scene {
            font-family: Georgia;
        }

        .personnage {
            text-align: center;
            text-transform: uppercase;
        }

        em{
          text-transform: none;
        }

        .texte {
            margin-bottom: 2em;
            margin-top: 1em;
        }
        </style>
    </head>
    <body>
      <div id="content">
        <h1>Le jeuxvideo.com de l'amour et du hasard</h1>

<?php
$wordCount = 0;
$acte = 1;
$pageCounter = 1;
while($wordCount < 50000){
  $scene = 1;
  $changeForum = (mt_rand() / mt_getrandmax() < 0.5 );
  $forumUrl = $changeForum ? "/forums/0-50-0-1-0-" . $pageCounter . "-0-blabla-15-18-ans.htm" : "/forums/0-51-0-1-0-" . $pageCounter . "-0-blabla-18-25-ans.htm";
  $forumUrl = $baseUrl . $forumUrl;
  echo '<h2>Acte ' . $acte . '</h2>';
  while($wordCount < 50000 && $scene < 6){
    $topics = getTopics($forumUrl);
    if(count($topics) > 0){
      $topic = $topics[array_rand($topics)];
      echo '<h3>Scène ' . $scene . ' : <a href="' . $topic->url . '">' . $topic->name . '</a></h3>';
      $wordCount += str_word_count($topic->name);
      echo '<div class="scene">';
      $posts = getPosts($topic->url);
      $posts = array_map("unserialize", array_unique(array_map("serialize", $posts)));
      foreach ($posts as $key => $post) {
        if(!empty($post->user)){
          $sentiments = getSentiments($post->text);
          $post->text = removeSmileys($post->text);
          echo '<div class="replique">';
          echo '<div class="personnage">' . trim($post->user);
          foreach ($sentiments as $key => $sent) {
            echo '<em>, ';
            if($key > 0)
              echo 'puis ';
            echo $sent . '</em>';
          }
          echo '</div>';
          echo '<div class="texte">' . $post->text . '</div>';
          echo '</div>';
          $wordCount += str_word_count($post->user);
          $wordCount += str_word_count($post->text);
        }
      }
      echo '</div>';
      $scene++;
    }
  }
  $acte++;
  $pageCounter += $changeForum ? 25 : 0;
}
?>
</div>
</body>
</html>
