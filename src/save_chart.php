<?php
   
	error_reporting(E_ALL);
	$milliseconds = round(microtime(true) * 1000);
    $filename = 'assets/charts/' . $milliseconds . '.png';

 try
 {
 
 if (!is_dir('assets/charts')) {
	   mkdir('assets/charts');
	}

	$path = "assets/charts";
$rdi = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
foreach (new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::SELF_FIRST) as $file => $info) {
     
	 $diff = time()-filemtime($info);
	$minutes = round($diff/60);
	
	//Delete files more than 6 hours old
	if($minutes > 120)
	{
		unlink($info);
	}
	 
}
	
	
	
	
	
    $src     = $_POST['src'];
	$data = $src;
    $src     = substr($src, strpos($src, ",") + 1);
    $decoded = base64_decode($src);
    
	list($type, $data) = explode(';', $data);
	list(, $data)      = explode(',', $data);
	$data = base64_decode($data);



file_put_contents($filename, $data);
	
	}
	catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}
	
	 echo $milliseconds;
?>