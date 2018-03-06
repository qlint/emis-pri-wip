<?php
if ( !empty( $_FILES ) ) {
	$dir = ( isset($_POST['dir']) ? $_POST['dir'] : 'other' );
    $tempPath = $_FILES[ 'file' ][ 'tmp_name' ];
    $uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;


	if (!file_exists($uploadPath)) {
	   mkdir($uploadPath);
	}

	 //move_uploaded_file( $tempPath, $uploadPath . $_FILES[ 'file' ][ 'name' ] );

	 file_put_contents($uploadPath . $_FILES[ 'file' ][ 'name' ], file_get_contents($tempPath));


    $answer = array( 'answer' => 'File transfer completed' );
    $json = json_encode( $answer );
    echo $json;
} else {
    echo 'No files';
}
// $uniqfolder = mkdir( 'assets/post/'.uniqid(attachment_), 0777 );
// if ( !empty( $_FILES ) ) {
//     $dirname = uniqid();
//     mkdir($dirname);
//     $tempPath = $_FILES[ 'file' ][ 'tmp_name' ];
//     $uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . mkdir( 'assets/post/email_uploads', 0777 ) . DIRECTORY_SEPARATOR . $_FILES[ 'file' ][ 'name' ];
//     move_uploaded_file( $tempPath, $uploadPath );
//     $answer = array( 'answer' => 'File transfer completed' );
//     $json = json_encode( $answer );
//     echo $json;
//     // copy('assets/posts/index.php', 'assets/posts/'.$uniqfolder.'/index.php');
// } else {
//     echo 'No files';
// }
?>
