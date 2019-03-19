<!DOCTYPE html>
<html>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

td, th {
    border: 1px solid #a6a6a6;
    text-align: center;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #f2f2f2;


}


div {
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
}
</style>
<body>

<?php

// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','Off');

require_once 'demo-lib.php';
demo_init(); // this just enables nicer output

// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit( 0 );

require_once 'DropboxClient.php';

/** you have to create an app at @see https://www.dropbox.com/developers/apps and enter details below: */
/** @noinspection SpellCheckingInspection */
$dropbox = new DropboxClient( array(
	'app_key' => "",      // Put your Dropbox API key here
	'app_secret' => "",   // Put your Dropbox API secret here
	'app_full_access' => false,
) );


/**
 * Dropbox will redirect the user here
 * @var string $return_url
 */
$return_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?auth_redirect=1";

// first, try to load existing access token
$bearer_token = demo_token_load( "bearer" );

if ( $bearer_token ) {
	$dropbox->SetBearerToken( $bearer_token );
	#echo "loaded bearer token: " . json_encode( $bearer_token, JSON_PRETTY_PRINT ) . "\n";
} elseif ( ! empty( $_GET['auth_redirect'] ) ) // are we coming from dropbox's auth page?
{
	// get & store bearer token
	$bearer_token = $dropbox->GetBearerToken( null, $return_url );
	demo_store_token( $bearer_token, "bearer" );
} elseif ( ! $dropbox->IsAuthorized() ) {
	// redirect user to Dropbox auth page
	$auth_url = $dropbox->BuildAuthorizeUrl( $return_url );
	die( "Authentication required. <a href='$auth_url'>Continue.</a>" );
}

?>
<?php

if(isset($_GET['upload'])){
	$test_file = $_FILES['uploadfile'];
	$file = $dropbox->GetFiles("",$recursive=false);
	foreach ($file as $key => $value) {
		if($value->name == $test_file['name']){
			echo "<h3>File Already exists!!</h3>";
			break;
		}
	}

	if($test_file['size'] != 0){
		echo "\n\n<b>Uploading $test_file:</b>\n";
		$dropbox->UploadFile( $test_file['tmp_name'], $test_file['name'] ) ;
		echo "\n done!";
	}
	else{
		echo "<h2>Please select a file to upload.</h2";
	}

}

if(isset($_GET['delete'])){
	$del_path = $_GET['delete'];
	$dropbox->Delete($del_path);
}


?>
<div>
<center>Photo-album application</center>
<form action="album.php?upload=True" method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="uploadfile" id="uploadfile">
    <input type="submit" value="Upload Image" name="submit">
</form>
</div>

<table>
	<tr>
		<th>Image</th>
		<th>Delete</th>
	</tr>
	<?php
	$file = $dropbox->GetFiles("",$recursive=false);
	foreach ($file as $key => $value) {
		echo "<tr>";
		echo "<td>";
		?>
		<a href="album.php?download=<?php echo $value->name ?>"><?php echo $value->name?></a> 
		<?php
		echo "</form>";
		echo "</td>";
		echo "<td>";
		echo "<form action='album.php?delete=".$value->path."' method='POST'>";
		echo "<input type='submit' name='delete' value='delete'></input>";
		echo "</td>";
	}	echo "</tr>";
	?>

</table>

<?php
if(isset($_GET['download'])){
	$file_path = $_GET['download'];
	$file = $dropbox->GetFiles("", $recursive=false);
	foreach ($file as $key => $value) {
		if($value->name == $file_path){
			$dropbox->DownloadFile($value, basename($value->path));
			echo "<img src='".$dropbox->GetLink($value, $preview=false)."'></img>";
		}
	}
	//echo "<img src='".$file_path."'></img>";

}
?>
</body>
</html>
