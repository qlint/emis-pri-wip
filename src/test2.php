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
    function debug_to_console( $data ) {
        $output = $data;
        if ( is_array( $output ) )
            $output = implode( ',', $output);

        echo "<script>console.log( 'php says: " . $output . "' );</script>";
    }
    $uploads = 8 * 10;
    debug_to_console( $uploads );
  ?>
  <script type="text/javascript">
  var today = new Date();
  var dd = today.getDate();
  var mm = today.getMonth()+1; //January is 0!
  var yyyy = today.getFullYear();

  if(dd<10) {
    dd = '0'+dd
  }

  if(mm<10) {
    mm = '0'+mm
  }

  today = dd + '/' + mm + '/' + yyyy;
  console.log(today);
  </script>
</body>
</html>
