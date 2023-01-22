<h1>Uploading posts</h1>



<p>Documentation [link to GitHub]</p>

<?php


/*
 _____          _        _    _____                 _
|  __ \        | |      | |  / ____|               (_)
| |__) |__  ___| |_ __ _| | | (___   ___ _ ____   ___  ___ ___
|  ___/ _ \/ __| __/ _` | |  \___ \ / _ \ '__\ \ / / |/ __/ _ \
| |  | (_) \__ \ || (_| | |  ____) |  __/ |   \ V /| | (_|  __/
|_|   \___/|___/\__\__,_|_| |_____/ \___|_|    \_/ |_|\___\___|

= = = = = = = = = = = = = = = = = = = = = = = =

v 0.9
Chris Silverman

= = = = = = = = = = = = = = = = = = = = = = = =


## OVERVIEW







TODO
- Better error handling


*/


/*
## NOTES

If you use this to upload a lot of images, you might get a 500 error. In my experience, the
danger zone is around 60-70 files, but this likely varies depending on server setup, available
memory, how big the files are, etc. Just refresh the page to continue uploading.

Keep in mind, though, that my use case for this is to upload individual WP posts as they're
created. Bulk-uploading large files is not really what I designed this for. If you have any
thoughts on how to improve this, I would love to know about them. I might, at some point, set up
an ajax-based approach where another script serves this one each image at a time, rather than
having one script take on 200 images. But maybe there's a better way? Anyway, you've been warned. 


## CREDIT WHERE CREDIT IS DUE

Slightly modified version of tlongren's jolly script here:
https://gist.github.com/tlongren/ebecf53d18ef712006d2aa53fce8f2a4

and GhostToast's reply here (which is from 2013, but still works for my purposes)
https://wordpress.stackexchange.com/questions/100838/how-to-set-featured-image-to-custom-post-from-outside-programmatically



// note that images need to have a full URL, like https://site.local/image.png


// This script converts JSON to associative arrays, but assumes that
// the .json files are in JSON format. This is a personal decision; I'm
// building this script to post to WP from iOS Shortcuts, and it's easier
// to generate data in JSON format in Shortcuts via dictionaries.



  INFO TEMPLATES
  ==============

  JSON
  ----

{
	"title": "eee",
	"post_meta_fields": {
		"meta 1": "hey",
		"meta 2": "it worked",
		"css": "yes"
	},
	"post_tags": [
		"tag1",
		"tag2",
		"tag3"
	]
}

  ARRAY
  -----

[
  'title' => 'asdfdfsf',
  'post_meta_fields' => [
    'meta 1' => 'hey',
    'css' => 'yes'
  ]
]



*/

/*
@todo
- Add HTML5 Boilerplate template
- Add styling
- Add the "uploading" screen, and then have it replaced when everything's been uploaded
- clean up code
- fix the thing where it throws a 500 if it has to process too many images
- the image file types it's looking for should be a config variable

- write brief explanatory docs
- share iOS shortcut
- Set up smallhacks page for this on csi.dev
*/


/*
  SETUP
  =====

  Put this in the plugins folder, even though it's not a plugin. This
  keeps it out of the root directory, which might have core files.

*/

// This is the path to your root WP installation

// 'info_file_type' - change to 'array' if the .json
// files are associative arrays

$wp_root = $_SERVER['DOCUMENT_ROOT'];
$public_directory_url = 'https://notes.art/wp-content/plugins/postalservice/files/';
$files_dir = $wp_root.'/wp-content/plugins/postalservice/files';
$posted_files_dir = $files_dir.'/_posted_items';
$info_file_type = 'json';


// If you're testing this on a public server, you might want to
// set 'post_status' to 'draft' first.

$settings = [
  'wp_root' => $wp_root,
  'public_directory_url' => $public_directory_url,
  'files_dir' => $files_dir,
  'info_file_type' => $info_file_type,
  'posted_files_dir' => $posted_files_dir,
  'post_status' => 'publish',
];

// Load WordPress
require_once $settings['wp_root'].'/wp-load.php';
require_once ABSPATH . '/wp-admin/includes/taxonomy.php';

//  for images
require_once(ABSPATH . '/wp-admin/includes/media.php');
require_once(ABSPATH . '/wp-admin/includes/file.php');
require_once(ABSPATH . '/wp-admin/includes/image.php');



/**
 * [insert_image_post description]
 * @param  string $image_path - public URL to the image file to upload
 * @param  array $post_data - all metadata that should go into the post
 * @return [type]            [description]
 */
function insert_image_post( $image_url, $post_data ) {
  global $settings;

  // Set the timezone so times are calculated correctly
  date_default_timezone_set('Europe/London');

  // I'm doing the following, rather than date('Y-m-d H:i:s'),
  // because the shared host I use does not have an NY timezone
  // and inserted posts are "scheduled" rather than posted, since
  // their creation time is five hours from now.
  $timezone = 'America/New_York';
  $timestamp = time();
  $date = new DateTime("now", new DateTimeZone($timezone));
  $post_date = $date->format('Y-m-d H:i:s');

  // the meta fields
  // since there might be multiple ones, I'm storing any
  // meta fields in their own array

  // Create post
  $post_id = wp_insert_post(array(
      'post_title'    => $post_data['title'] ?? 'title',
      'post_content'  => $post_data['content'] ?? '',
      'post_date'     => $post_data['post_date'], // ?? $post_date,
      'post_author'   => $post_data['user_id'] ?? 1,
      'post_type'     => $post_data['post_type'] ?? 'post',
      'post_status'   => $post_data['post_status'] ?? $settings['post_status'],
      'post_category' => $post_data['post_cats'] ?? null,
      'tags_input'    => $post_data['post_tags'] // ?? null
  ));

  //  If this was successful, we now have an ID for the new post.
  if ( $post_id ) {
    
    

    // Set category - create if it doesn't exist yet
    wp_set_post_terms($post_id, wp_create_category('My Category'), 'category');

    // Add meta data, if required

    print_r( $post_data['post_meta_fields'] );

    foreach ( $post_data['post_meta_fields'] as $key => $value ) {
      add_post_meta($post_id, $key, $value);
    }
    echo '<div class="status-item status-success"><a href="' . get_permalink( $post_id ) . '">'.$post_data['title'].'<span class="status-symbol symbol--success">success</span></a></div>';
  } else {
    echo '<div class="status-item status-failure">'.$post_data['title'].'<span class="status-symbol symbol--failure">failed</span></div>';
  }

  // magic sideload image returns an HTML image, not an ID
  // by default, what it returns is a populated img tag. I want just the URL, so I need to specify
  // a return_type of 'src' 
  $media = media_sideload_image($image_url, $post_id, null, 'src');
  echo '$media = ' . $media . '<br><hr>';
  
  
  
  // So here's an issue. $media returns the URL of the newly uploaded image. That's a full
  // URL that includes the domain name of your site.
  //
  // But if you're using something like Jetpack, the images will be uploaded to the
  // Jetpack CDN and the URL will change to the image's URL on the CDN (i0.wp.com or something)
  // This poses a problem when, later on, we're comparing the URL of the image we just uploaded to
  // the attachment's URL (which we'll obtain via wp_get_attachment_image_src()). The CDN URL might also
  // have additional parameters like ?fit=900%2C1526&ssl=1
  //
  // So to make sure the comparison doesn't fail, we need to extract the raw filename from both URLs,
  // since that's the one thing that shouldn't change.
  //
  // 

  $sideloaded_imagename = pathinfo($media);
  $sideloaded_imagename = $sideloaded_imagename['filename'];

  // So if $media isn't empty or an error - that is, the image was successfully sideloaded - we now
  // have to get more details about it so we can specify it as a featured image for the post
  // we just created. So let's find that image.
  
  if(!empty($media) && !is_wp_error($media)) {
    $args = array(
      'post_type' => 'attachment',
      'posts_per_page' => -1,
      'post_status' => 'any',
      'post_parent' => $post_id
    );
    
    // find all attachments that have a post parent ID of the one we just created
    $attachments = get_posts($args);

        
    if(isset($attachments) && is_array($attachments)) {

      foreach($attachments as $attachment) {
        // grab source of full size images (so no 300x150 nonsense in path)
        $image = wp_get_attachment_image_src($attachment->ID, 'full');

        // set alt text
        $img_alt_text = $post_data['img_alt'] ?? '';
        update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $img_alt_text );
        
        // determine if in the $media image we created, the string of the URL exists

        $attachment_filename = pathinfo($image[0]);
        $attachment_filename = $attachment_filename['filename'];

        if($sideloaded_imagename == $attachment_filename) {
          // if so, we found our image. set it as thumbnail
          $was_set = set_post_thumbnail($post_id, $attachment->ID);
          // only want one image
          echo '$post_id = ' . $post_id . ', $was_set = ' . $was_set;
          break;
        }
      }
    }
    echo "no error";
  }
  else echo "error " . wp_error($media);
}

function scan_folder( $folder ) {

  global $settings;

  // get all image files
  $files = glob("$folder/*.{jpg,JPG,jpeg,JPEG,gif,GIF,png,PNG}", GLOB_BRACE);

  foreach ( $files as &$file ) {

    // let's turn this path into something that we
    // can extract data from
    $path_parts = pathinfo( $file );

    // this is the name of the image file (photo.jpg)
    $image_basename = $path_parts['basename'];

    // this is the name of the image file without the
    // extension (photo)
    $basic_filename = $path_parts['filename'];

    // after the file is posted to WordPress, it will be moved
    // to the following path
    $file_posted_path = $settings['posted_files_dir'].'/'.$image_basename;

    // this is the corresponding info file
    $info_file_path = $folder.'/'.$basic_filename.'.json';
    

    // after the file is posted, its info file
    // will be moved to the following path
    $info_file_posted_path = $settings['posted_files_dir'].'/'.$basic_filename.'.json';


echo 'info file path ' . $info_file_path;

    // load the info file
    $info_file_data = file_get_contents( $info_file_path );

    // does this need to be converted to an array?
    if( $settings['info_file_type'] != 'array' )
      $info_file_data = json_decode( $info_file_data, true );

    // the public image path
    $image_url = $settings['public_directory_url'] . $path_parts['basename'];

    insert_image_post( $image_url, $info_file_data );

    // https://stackoverflow.com/questions/19139434/php-move-a-file-into-a-different-folder-on-the-server

    rename($file, $file_posted_path);
    rename($info_file_path, $info_file_posted_path);

  }
  unset($file);
}



scan_folder( $settings['files_dir']);
