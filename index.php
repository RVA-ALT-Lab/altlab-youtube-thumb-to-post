<?php 
/*
Plugin Name: ALT Lab YouTube Thumbnail to Post
Plugin URI:  https://github.com/
Description: Get a youtube thumbnail and set it as the featured image of a post for COBE
Version:     1.2
Author:      ALT Lab (Matt Roberts)
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: my-toolset

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );



function get_email($content) {
    $currentUser = wp_get_current_user();
    // var_dump($currentUser->user_email);
    $postID = get_the_ID();
    $checker = reviewer_check($postID, $currentUser);
    //Here the category is unique to the COBE site
    if ( has_category( 158, $post->ID ) && $checker != FALSE ) {
        // print("<pre>".print_r(reviewer_check($postID, $currentUser),true)."</pre>");
        return $checker . strip_shortcodes($content);
    }
    else {
        return $content;
    }
}

add_filter( 'the_content', 'get_email' );

//Here the search for the PostID and user_email is unique to the COBE site
function reviewer_check($postID, $currentUser) {
    $search_criteria = array(
        'status'        => 'active',
        'field_filters' => array(
            'mode' => 'all',
            array(
                'key'   => '7',
                'value' => $postID
            ),
            array(
                'key'   => '8',
                'value' => $currentUser->user_email
            )
        )
    );
    //Here the GF field IDs are unique to the COBE site
    $entries         = GFAPI::get_entries( 2, $search_criteria );
    if ( $entries ) {
        $score_A = $entries[0]['4'];
        $score_B = $entries[0]['5'];
        $score_C = $entries[0]['6'];
        $html = "<div class='video-text'>You gave this video the following score:</div>
                    <ul class='video-scores'>
                        <li>STORY: $score_A</li>
                        <li>TRANSLATION: $score_B</li>
                        <li>IMPACT: $score_C</li>
                    </ul>
                    <p><a href='http://cobe.vcu.edu/programs/rams-research-roundup/video-submission-gallery/'>Back to the Gallery</a></p>";
        return $html;
    }
    else { return FALSE;
    }
}


function getYouTubeVideoId($pageVideUrl) {
    $link = $pageVideUrl;
    $video_id = explode("?v=", $link);
    if (!isset($video_id[1])) {
        $video_id = explode("youtu.be/", $link);
    }
    $youtubeID = $video_id[1];
    if (empty($video_id[1])) $video_id = explode("/v/", $link);
    $video_id = explode("&", $video_id[1]);
    $youtubeVideoID = $video_id[0];
    if ($youtubeVideoID) {
        return $youtubeVideoID;
    } else {
        return false;
    }
}

//Get the YouTube URL Gravity Form field after submission
add_action("gform_after_submission_1", "after_submission", 10, 2);
function after_submission($entry, $form) {
   $pageVideUrl = $entry["3"];
//    print_r($pageVideUrl);
//Get the YouTube Thumbnail
   $youtubeID = getYouTubeVideoId($pageVideUrl);
   $thumbURL = 'https://img.youtube.com/vi/' . $youtubeID . '/mqdefault.jpg';
//    print_r($thumbURL);
    $post_id = get_post( $entry['post_id']);
set_feature_vid_image($thumbURL, $post_id);
}

function set_feature_vid_image($thumbURL, $post_id) {
    // Add Featured Image to Post
    $image_url        = $thumbURL; // Define the image URL here

    $image_name       = 'video-thumbnail.png';
    $upload_dir       = wp_upload_dir(); // Set upload folder
    $image_data       = file_get_contents($image_url); // Get image data
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
    $filename         = basename( $unique_file_name ); // Create image file name

    // Check folder permission and define file location
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    // Create the image  file on the server
    file_put_contents( $file, $image_data );

    // Check image file type
    $wp_filetype = wp_check_filetype( $filename, null );

    // Set attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // And finally assign featured image to post
    set_post_thumbnail( $post_id, $attach_id );
}


//LOGGER -- like frogger but more useful

if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}