<?php
/*
  Plugin Name: Remote Upload
  Plugin URI: http://ciphercoin.com/
  Description: Upload files from url to your website. Just enter file urls in input box, and it will automatically download the files to your website.  
  Author: arshidkv12 
  Author URI: http://ciphercoin.com/
  Text Domain: remote-upload
  Version: 1.0.0
*/ 
 


/*  add menue in admin */
add_action( 'admin_menu', 'remote_upload_menu' );

/** Step 1. */
function remote_upload_menu() {
	 wp_enqueue_style( 'remote_upload_style',  plugin_dir_url( __FILE__ )  . 'style.css');
	//wp_enqueue_script('upload_js' plugin_dir_url( __FILE__ ).'upload.js');
	add_options_page( 'Remote upload Options', 'Remote Upload', 'manage_options', 'remote-upload', 'remote_upload_options' );
}

/** Step 3. */
function remote_upload_options() {
	if ( !current_user_can( 'activate_plugins' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$nonce = wp_create_nonce( 'remote-upload-nonce' );
	?>

	<div class=""></div>
	<p class="selectionShareable"> Add multiple file URLs:<br> <small>(Enter one URL per line).</small></p>
	<form  action='options-general.php?page=remote-upload&_wpnonce=<?php echo $nonce; ?>' method="POST">
	 <textarea wrap="off" placeholder="http://example.com/file.mp3" rows="15" cols="100" name='file_urls' id="file_url"></textarea>
	 <input class='submit' type="submit">
	</form>

	<div id="content"></div>
	<div id="loader" style="height:19px;"></div>

	<?php
      
}



function remote_upload_json(){

	if ( !current_user_can( 'activate_plugins' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}


	if(isset($_POST['file_urls']) && ($_POST['file_urls'] != null)){

	 $nonce = $_REQUEST['_wpnonce'];
	 if ( wp_verify_nonce( $nonce, 'remote-upload-nonce')):

		$upload_dir = wp_upload_dir();
		if (!file_exists($upload_dir['path'])) {
		    mkdir($upload_dir['path'], 0777, true);
	        $file = fopen($upload_dir['path']."/remote_upload_progress.json","w");
	        $json_data =  array('percentage' =>  '0' );
			fwrite($file,json_encode($json_data));
			fclose($file);
			chmod($file,0777);
		}

		$file = fopen($upload_dir['path']."/remote_upload_progress.json","w");
	    $json_data =  array('percentage' =>  '0', 'total_url_count' => '0' ,'url_count' => '0');
		fwrite($file,json_encode($json_data));
		fclose($file);

     	$file_urls = $_POST['file_urls'];  
     	//esc_sql is checking induvidually. It is storing to database (jQuery) GET method. 

	    $file_json_url =  admin_url('options-general.php?page=remote-upload&json_url=true');
	   	$post_file_url = admin_url('/options-general.php?page=remote-upload&post_file=true');
	       
  		wp_enqueue_script( 'upload_js_library', plugins_url('js/upload.js',__FILE__ ), array('jquery'), '1.0.0'); 		
  		
  		$upload_dir = wp_upload_dir();
 		$data = array('file_json_url'=> $file_json_url ,
	    'post_file_url' => $post_file_url,
	    'file_urls' => $file_urls,
	    'json_data' => $upload_dir['url']."/remote_upload_progress.json",
		);

		wp_localize_script( 'upload_js_library', 'php_vars', $data );

	  endif;
	}

	if(isset($_GET['post_file']) && ($_GET['post_file'] == 'true')){

			function is_session_started_for_upload(){
			            if ( php_sapi_name() !== 'cli' ) {
			                if ( version_compare(phpversion(), '5.4.0', '>=') ) {
			                    return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
			                } else {
			                    return session_id() === '' ? FALSE : TRUE;
			                }
			            }
			            return FALSE;
			        }
			              
			      if (is_session_started_for_upload() === FALSE ) session_start();

		 	/* Get file total size */ 
		 	$file_urls = trim($_POST['file_urls']);
		 	$file_urls = explode ("\r\n", $file_urls);
		 	$file_urls = array_filter($file_urls, 'trim');

		 	//$file_urls = array_values(array_filter(explode ("\r\n", $file_urls)));
			
			$upload_dir = wp_upload_dir();
			$file = fopen($upload_dir['path']."/remote_upload_progress.json","w");
         	$json_data =  array('percentage' =>  '0', 'total_url_count' => '0' ,'url_count' => '0');
			fputs($file,json_encode($json_data));
			fclose($file);

		 	for ($i = 0; $i <= count($file_urls); $i++):
			    // processing here.

			 	$file_url = esc_url(trim($file_urls[$i]));
			 	$_SESSION['file_url'] =  esc_url(trim($file_urls[$i]));
				preg_match("/[^\/]+$/", $file_url, $matches);
				$file_name = $matches[0];
				$_SESSION['complete'] = false;
				$_SESSION['percentage'] = 0;
				$_SESSION['total_url_count'] = count($file_urls);
				$_SESSION['url_count'] = $i; 

			    //Save file 
				set_time_limit(0);
				//This is the file where we save the    information
				$upload_dir = wp_upload_dir();
				$fp = fopen ($upload_dir['path']."/"  . utf8_decode(urldecode($file_name)), 'w+');
				//Here is the file we are downloading, replace spaces with %20
				$ch = curl_init(str_replace(" ","%20",$file_url));

				curl_setopt($ch, CURLOPT_TIMEOUT, 30000);
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($source,$download_size, $downloaded, $upload_size, $uploaded){
				   
				    if($download_size > 0){
						 $upload_dir = wp_upload_dir();
				         $perc =  round($downloaded / $download_size  * 100);

						 //if($perc != 100 ){ 
						 	$file = fopen($upload_dir['path']."/remote_upload_progress.json","w");
				         	$json_data =  array('percentage' =>  $perc, 'total_url_count' => $_SESSION['total_url_count'] ,'url_count' => $_SESSION['url_count']);
							fputs($file,json_encode($json_data));
							fclose($file);
						 //}
	    
					    if($perc == 100 ){ 	
					    	if($_SESSION['complete'] == false){
					    	    
					    	    $_SESSION['complete'] = true;
							    //here database
 
					    	}

					    }
				    }
				     //ob_flush();
				    //flush();
				    //sleep(1); // just to see effect



				});
				curl_setopt($ch, CURLOPT_FILE, $fp); 
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				// get curl response
				$data = curl_exec($ch); 
				curl_close($ch);
				fclose($fp); 
				chmod($upload_dir['path']."/"  . utf8_decode(urldecode($file_name)), 0777);
			 	$file_name = utf8_decode(urldecode($file_name));

					$json_out = array('post' => 'true' );
					//wp_send_json($json_out);

				if($_SESSION['complete'] == true):

					// Create post object
		    	    $file_url = $_SESSION['file_url'] ;
					preg_match("/[^\/]+$/", $file_url, $matches);
					$file_name = $matches[0];
					$file_name = utf8_decode(urldecode($file_name));
		    	    $post_title = substr($file_name, 0, strrpos($file_name, "."));
		    	    
		    	    $finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime_type = finfo_file($finfo,  $upload_dir['path']."/"  . $file_name);
					finfo_close($finfo);

					$post_data = array(
					  'post_title'    =>  esc_sql($post_title),
					  'post_name'  => esc_sql($post_title),
					  'post_status'   => 'inherit',
					  'guid' => esc_sql($upload_dir['url']."/".str_replace(" ","%20",$file_name)),
					  'post_mime_type' => esc_sql($mime_type) ,
					  'post_type'   => 'attachment'

					);

					// Insert the post into the database
					$post_id = wp_insert_post( $post_data );
					$file_path = ltrim($upload_dir['subdir']."/".$file_name,'/');
 					add_post_meta($post_id, '_wp_attached_file',  esc_sql($file_path));

					if (strpos($mime_type,'image') !== FALSE) {
						//generate post_meta for image 
						$data = wp_generate_attachment_metadata($post_id,$upload_dir['path'].'/'.$file_name);
						wp_update_attachment_metadata( $post_id, $data );
					}

					if (strpos($mime_type,'audio') !== FALSE) {
						//generate post_meta for audio 
						$data = wp_read_audio_metadata($upload_dir['path'].'/'.$file_name);
						wp_update_attachment_metadata( $post_id, $data );
					}

					if (strpos($mime_type,'video') !== FALSE) {
						//generate post_meta for video 
						$data = wp_read_video_metadata($upload_dir['path'].'/'.$file_name);
						wp_update_attachment_metadata( $post_id, $data );
					}

				   // $file = fopen($upload_dir['path']."/remote_upload_progress.json","w");
				   // $json_data =  array('percentage' =>  'done' );
				   // fputs($file,json_encode($json_data));
				 	//fclose($file);
				endif;
			 endfor; 

			$file = fopen($upload_dir['path']."/remote_upload_progress.json","w");
	        $json_data =  array('percentage' =>  'done..!' );
			fputs($file,json_encode($json_data));
			fclose($file);


		}


		if (isset($_GET['json_url']) && ($_GET['json_url'] == true)) {

			if(isset($_SESSION['percentage'])){
				if($_SESSION['percentage'] == 100){
					 //$_SESSION['percentage'] = 'done';
				}
				$json_out = array('percentage' => $_SESSION['percentage'] );
				wp_send_json($json_out); 
			} 
			

		}
 

}
add_action( 'admin_init', 'remote_upload_json', 1 );



// Add settings link on plugin page
function remote_upload_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=remote-upload">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'remote_upload_settings_link' );

?>