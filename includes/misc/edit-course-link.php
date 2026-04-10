<?php 
/**
 * Edit course in Kursagenten
 * @return string
 */
function edit_course(){
    if( current_user_can('edit_others_pages') ) {
	$kursid = get_field('id' );
    //$kursid = get_post_meta( $post_id, $key = 'id', $single = true );
	//return "hei på meg" . $kursid;
		if ($kursid) {
			return "<a href='https://kursadmin.kursagenten.no/LegacyIframe/Course/?courseId=" . $kursid . "' class='edit_course' target='_blank'>Rediger kurset på Kursagenten</a>";
		}
		}else{
			return "";
		};
}
add_shortcode('edit_course', 'edit_course');