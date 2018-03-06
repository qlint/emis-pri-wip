<?php
header('Access-Control-Allow-Origin: *');
?>

<html lang="en">
<head>
  <meta charset="utf-8">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.2/jquery.js" type="text/javascript"></script>
<title>Test</title>
  <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
  <![endif]-->
</head>

<body>
  <?php

  var_dump($_SERVER['DOCUMENT_ROOT']);
  echo "<br><br>";
  var_dump(getcwd());
  echo "<br><br>";
  $scanthis = $_SERVER['DOCUMENT_ROOT'];
  $showresults = scandir($scanthis, 1);
  var_dump($showresults);
  echo "<br><br>";
  var_dump($_SERVER['HTTP_HOST']);
  echo "<br><br>";
  var_dump(__DIR__);
  echo "<br><br>";
  var_dump(realpath(__DIR__ . "/../api"));

  ?>
</body>
</html>
