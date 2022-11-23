<?php

header('Content-Type: text/html; charset=utf-8');

include '../wp-load.php';
// include '../wp-includes/post.php';

// it allows us to use download_url() and wp_handle_sideload() functions
include '../wp-admin/includes/file.php';

$data = json_decode(file_get_contents('php://input'));
// return attachment id
function upload_file_by_url( $image_url ) {

	// download to temp dir
	$temp_file = download_url( $image_url );
    
	if( is_wp_error( $temp_file ) ) {
		return "fuck1";
	}

    $name = basename( $image_url );
    
    $name = substr($name, 0 , 5);
    $name = $name.=".jpg";
	// move the temp file into the uploads directory
	$file = array(
		'name'     => $name,
		'type'     => mime_content_type( $temp_file ),
		'tmp_name' => $temp_file,
		'size'     => filesize( $temp_file ),
	);
	
	$sideload = wp_handle_sideload(
		$file,
		array(
			'test_form'   => false // no needs to check 'action' parameter
		)
	);

	if( ! empty( $sideload[ 'error' ] ) ) {
		// you may return error message if you want
		return "fuck2";
	}

	// it is time to add our uploaded image into WordPress media library
	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => $sideload[ 'url' ],
			'post_mime_type' => $sideload[ 'type' ],
			'post_title'     => basename( $sideload[ 'file' ] ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$sideload[ 'file' ]
	);

	if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return "fuck3";
	}

	// update medatata, regenerate image sizes
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
	);
    @ unlink($temp_file);
	return $attachment_id;

}

switch ($data->type){
    case 'confirmation':
		//past here confirmation code
        echo 'aaaaaaaa';
        exit();
        break;
    case 'wall_post_new':
        $post_text = $data->object->text;
        $post_images = '';
        $title_url='';
        foreach($data->object->attachments as $value){
            if ($value->type == 'photo'){
                $pic_array = $value->photo->sizes;
                $max_image=sizeof($pic_array) - 1;
                $pic_url=$pic_array[$max_image]->url;
                if ($title_url == ""){
                    $title_url = $pic_url;
                }
                $pic_html='<br><img src="'.$pic_url.'" alt="Тут должна быть картинка" style="margin-top:15px"/>';
                $post_images.=$pic_html;
            }
        }
        $first_enter = strpos($post_text, PHP_EOL);
        $post_title = substr($post_text, 0, $first_enter);
        $post_text = substr($post_text, $first_enter);
        
        $post_text .= $post_images;
        $featureed_pic_id = upload_file_by_url($title_url);
        
        
        $post_data = array(
            'post_title' => $post_title,
            'post_content' => $post_text,
            'post_status' => 'publish',
        );

        $post_id = wp_insert_post($post_data);
        
        $result = set_post_thumbnail($post_id,$featureed_pic_id);
        
        break;
        
}

echo "OK";