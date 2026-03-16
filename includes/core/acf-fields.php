<?php 

if( function_exists('acf_add_options_page') ) {
	
	$parent = acf_add_options_page(array(
		'page_title' 	=> 'User Portal Settings',
		'menu_title'	=> 'User Portal Settings',
		'menu_slug' 	=> 'user-portal-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
	
	acf_add_options_sub_page(array(
		'page_title' 	=> 'Email Settings',
		'menu_title'	=> 'Email Settings',
		'capability'	=> 'edit_posts',
		'parent_slug'	=> $parent['menu_slug']
	));
	
	acf_add_options_sub_page(array(
		'page_title' 	=> 'Form Settings',
		'menu_title'	=> 'Form Settings',
		'capability'	=> 'edit_posts',
		'parent_slug'	=> $parent['menu_slug']
	));
}

add_action( 'acf/save_post', 'fx_up_after_acf_save', 20 );

function fx_up_after_acf_save( $post_id ) {
    if ( ! isset( $_POST['acf'] ) || ! isset( $_POST['post_type'] ) || ( 'villa' !== $_POST['post_type'] ) ) {
        return;
    }

    $video    = get_field( 'introductory_video', $post_id );
    $video_id = get_youtube_id( $video );

    $video_thumbnail = get_field( 'introductory_video_thumbnail', $post_id );

    if ( ! empty( $video ) && ( strpos( $video, 'youtube' ) !== false ) && ! $video_thumbnail  ) {
        $video_thumbnail_id = fxup_sideload_image( 'https://img.youtube.com/vi/' . $video_id . '/0.jpg' );
        if ( $video_thumbnail_id ) {
            update_post_meta( $post_id, 'introductory_video_thumbnail', $video_thumbnail_id );
            update_post_meta( $post_id, '_introductory_video_thumbnail', 'field_60d30ae4eaffe' );
        }
    }
}

?>