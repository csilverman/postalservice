<!doctype html>
<html class="no-js" lang="">

<head>
  <meta name="robots" content="noindex">
  <meta charset="utf-8">
  <title>PostalService</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.min.css">

</head>

<body>

<h1><a href="https://github.com/csilverman/postalservice">PostalService</a></h1>
<small>by <a href="https://csilverman.com/">Chris Silverman</a> - v1.0</small>

<?php

/*
 _____          _        _    _____                 _
|  __ \        | |      | |  / ____|               (_)
| |__) |__  ___| |_ __ _| | | (___   ___ _ ____   ___  ___ ___
|  ___/ _ \/ __| __/ _` | |  \___  \/ _ \ '__\ \ / / |/ __/ _ \
| |  | (_) \__ \ || (_| | |  ____) |  __/ |   \ V /| | (_|  __/
|_|   \___/|___/\__\__,_|_| |_____/ \___|_|    \_/ |_|\___\___|

= = = = = = = = = = = = = = = = = = = = = = = =

v 1.0
Chris Silverman

= = = = = = = = = = = = = = = = = = = = = = = =

## OVERVIEW

PostalService is a small PHP script that does the following:

- Scans a folder of files. For every image file it finds, it looks for a
  JSON file with the same filename.
- It then creates a new WordPress post from the data in that JSON file, and
  sets the image file as the featured image for that post
- Finally, it moves both the image and JSON file into a subfolder named _posted_items
  so things don't get posted twice.


## CREDIT WHERE CREDIT IS DUE

tlongren's excellent script here was very helpful:
https://gist.github.com/tlongren/ebecf53d18ef712006d2aa53fce8f2a4

// This script converts JSON to associative arrays, but assumes that
// the .json files are in JSON format. This is a personal decision; I'm
// building this script to post to WP from iOS Shortcuts, and it's easier
// to generate data in JSON format in Shortcuts via dictionaries.



  INFO TEMPLATES
  ==============

  JSON
  ----

{
  "title": "Hello World",
  "content": "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam interdum mollis laoreet. Curabitur ullamcorper pulvinar metus eu congue. Quisque tempus nisi id dapibus tincidunt. Etiam ut arcu a orci iaculis placerat vel eu nisi. Quisque tincidunt risus at est tristique, in volutpat diam vehicula. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Maecenas ullamcorper, felis in egestas eleifend, nisl quam viverra leo, id blandit eros lectus sed mauris. Duis facilisis tempor faucibus. Pellentesque ac dapibus velit. Sed id metus ac sapien aliquam pretium eget non est. Vestibulum auctor pharetra interdum. Donec aliquet lectus vel metus consequat congue. Nunc ornare sagittis egestas.</p><p>Ut eleifend vel dui vitae sagittis. Etiam eget est viverra, tincidunt nibh vel, dapibus dolor. Maecenas dolor augue, tristique id hendrerit mollis, dignissim in diam. Etiam eu metus mauris. Duis nisi ligula, accumsan quis convallis sit amet, sollicitudin non erat. Nam dignissim tempus quam non vulputate. Donec interdum eget libero a pellentesque. Mauris placerat non libero eget maximus.</p>",
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
  'title' => 'Hello World',
  'post_meta_fields' => [
    'meta 1' => 'hey',
    'meta 2': 'it worked',
    'css' => 'yes'
  ],
  'post_tags' => [
    'tag1',
    'tag2',
    'tag3'
  ]  
]



*/

/*
@todo
- Add HTML5 Boilerplate template
- Make this prettier:
  - styling for the statuses displayed by insert_image_post()
  - overall niceness for the page
- Add the "uploading" screen, and then have it replaced when everything's been uploaded
- fix the thing where it throws a 500 if it has to process too many images
- better error management. insert_image_post() could fail at two points: either creating a post or attaching an image. If either of these fails for some reason, it should (1) return false, and (2) give some indication as to the nature of the problem.
*/


//  SETUP
//  ================

//  You'll typically need to configure these:

//  If your WordPress installation isn't at the root of your server,
//  you'll need to specify its root path here.
$ps__wp_root = $_SERVER['DOCUMENT_ROOT'];

$ps__the_main_folder = 'postalservice';
$ps__public_basefolder = 'https://wp20230604.csilverman.com/wp-content/plugins/';

//  if this isn't accurate, your posts might have the wrong date.
$ps__timezone = 'America/New_York';

$ps__image_types = 'jpg,JPG,jpeg,JPEG,gif,GIF,png,PNG';

//  If you're testing this on a public server, you might want to set 'post_status' to 'draft' first.
$ps__default_post_status = 'draft';


//  If you're storing PostalService anywhere other than the WordPress /plugins
//  folder, or you want to use different folder names for some reason, you'll
//  need to change these:

//  This needs to be accurate in order to sideload an image.
$ps__public_directory_url = $ps__public_basefolder.$ps__the_main_folder.'/files/';

//  This is where PostalService looks for files it needs to process
$ps__files_dir = $ps__wp_root.'/wp-content/plugins/'.$ps__the_main_folder.'/files';

//  This is where files are moved after they've been processed
$ps__posted_files_dir = $ps__files_dir.'/_posted_items';

//  change to 'array' if the .json files are associative arrays for some reason
$ps__info_file_type = 'json';


//  ================
//  Don't change anything beyond this point.
//  ----------------------------------------

$settings = [
  'wp_root' => $ps__wp_root,
  'public_directory_url' => $ps__public_directory_url,
  'files_dir' => $ps__files_dir,
  'main_folder' => $ps__the_main_folder,
  'info_file_type' => $ps__info_file_type,
  'posted_files_dir' => $ps__posted_files_dir,
  'post_status' => $ps__default_post_status,
  'timezone' => $ps__timezone,
  'imagetypes' => $ps__image_types,
];

// Load WordPress
require_once $settings['wp_root'].'/wp-load.php';
require_once(ABSPATH . '/wp-admin/includes/taxonomy.php');

//  for images
require_once(ABSPATH . '/wp-admin/includes/media.php');
require_once(ABSPATH . '/wp-admin/includes/file.php');
require_once(ABSPATH . '/wp-admin/includes/image.php');



/**
 * insert_image_post()
 * This accepts post data - content and image - and creates a new WordPress post, with the image as the featured image.
 * 
 * @param string $image_url: public URL to the image file to insert. This must be a full URL, like https://site.local/image.png
 * @param array $post_data: all the post's metadata and content
 * @return: this does not currently return anything (see "better error management" under "todo")
 */
function insert_image_post( $image_url, $post_data ) {
  global $settings;
  
  $image_url_parts = pathinfo( $image_url );
  $image_filename = $image_url_parts['basename'];
  
  echo '<h3>'.$image_filename.'</h3>';
  
  //  PART 1: Create a new WordPress post.
  //  ------

  // Set the timezone so times are calculated correctly
  date_default_timezone_set('Europe/London');

  // I'm doing the following, rather than date('Y-m-d H:i:s'),
  // because the shared host I use does not have an NY timezone (I'm in NY)
  // and inserted posts are "scheduled" rather than posted, since
  // their creation time is five hours from now.
  $ps__timezone = $settings['timezone'];
  $timestamp = time();
  $date = new DateTime("now", new DateTimeZone($ps__timezone));
  $post_date = $date->format('Y-m-d H:i:s');

  // Now we create a new WordPress post.
  $post_id = wp_insert_post(array(
      'post_title'    => $post_data['title'] ?? 'title',
      'post_content'  => $post_data['content'] ?? '',
      'post_date'     => $post_data['post_date'] ?? $post_date,
      'post_author'   => $post_data['user_id'] ?? 1,
      'post_type'     => $post_data['post_type'] ?? 'post',
      'post_status'   => $post_data['post_status'] ?? $settings['post_status'],
      'post_category' => $post_data['post_cats'] ?? null,
      'tags_input'    => $post_data['post_tags'] ?? null
  ));

  //  If this was successful, we now have an ID for the new post.
  if ( $post_id ) {

    // Set the post's category, and create one if it doesn't exist yet
    wp_set_post_terms($post_id, wp_create_category('My Category'), 'category');

    // Now add any custom fields, if there were any.
    foreach ( $post_data['post_meta_fields'] as $key => $value ) {
      add_post_meta($post_id, $key, $value);
    }
    
    $new_post_link = '<a href="' . get_permalink( $post_id ) . '">'.$post_data['title'].'</a>';
    
    echo '</p>Successfully created post '.$new_post_link.'</p>';
  } else {
    echo '<div class="status-item status-failure">'.$post_data['title'].'<span class="status-symbol symbol--failure">failed</span></div>';
  }
  

  //  PART 2: add the image.
  //  ------

  //  The basic strategy:
  //  1. add the image to WordPress's media library via media_sideload_image()
  //  2. once it's in there, specify it as the featured image for the post we created.

  //  By default, media_sideload_image() returns the HTML markup for the
  //  sideloaded image, not an ID. So we have to specify a return_type of 'id'.
  
  //  This associates an image file ($image_url) with a post ($post_id). Note that
  //  this does *not* mean the image is now the post's featured image; we'll have to do that
  //  next. This just creates an attachment - basically a WordPress post specifically for
  //  media, like an image - and associates that attachment with the WordPress post we specified. 
  $media = media_sideload_image($image_url, $post_id, null, 'id');

  //  Oh, also.
  //  I was getting a "Invalid image URL" error at one point, which was confusing,
  //  since the image URL was valid. However, the image itself was not - it was
  //  corrupt for some reason - so if you get any weird errors, make sure you're
  //  working with a usable image! 
  if(is_wp_error($media)) {

    //  @todo: This is one place where I should be setting an error variable to return later
    echo "<p><strong>Error sideloading image.</strong> <code>media_sideload_image()</code> returned the following error: <code>" . $media->get_error_message() . '</code></p><p><strong>Possible causes:</strong></p><ul><li>The image file is corrupted.</li></ul>';
  }
  else {
    echo '<p>Successfully created <a href="' . get_permalink( $media ) . '">this attachment</a>.</p>';
   // Now apply its alt text, specified earlier in the post_data array.
     $img_alt_text = $post_data['img_alt'] ?? '';
     update_post_meta( $media, '_wp_attachment_image_alt', $img_alt_text );
     
     //set it as thumbnail
     $was_set = set_post_thumbnail($post_id, $media);
    
    echo '<p>Successfully attached <a href="' . get_permalink( $media ) . '">this image</a> to post '.$new_post_link.'.</p>' ;    
  }
}


/**
 * scan_folder()
 * This scans a given path ///
 * 
 * @param string $folder: a path to a folder with images/JSON files you want to post to WordPress
 * @return: this does not currently return anything (see "better error management" under "todo")
 */

function scan_folder( $folder ) {

  global $settings;

  // get all image files
  $imagetypes = $settings['imagetypes'];

  //  This returns an array of all image files in the provided folder (or at
  //  least images with the extensions specified in the `imagetypes` setting
  $files = glob($folder.'/*.{'.$imagetypes.'}', GLOB_BRACE);

  if( $files ) {
    
  }
  else {
    echo '<p><strong>ℹ️ No files of type <code>'.$imagetypes.'</code> were
    found in '.$folder.'.</strong></p>
    <p>Check your files folder. Remember that <a href="https://github.com/csilverman/postalservice#images-are-required-for-posts">all JSON files must have an accompanying image</a>; JSON files by themselves will not be posted.</p>';
  }

  //  Run through those files
  foreach ( $files as &$file ) {

    //  $file is the full path to each image file. What we need instead is
    //  - the name of the image file, with extension
    //  - the name of the image file without the extension, since adding
    //    .json to that is how we get the file that contains the post content
    //    associated with each image.
    
    //  So let's turn this path into something that we can get the above from.
    $path_parts = pathinfo( $file );

    //  This is the name of the image file ("photo.jpg")
    $image_basename = $path_parts['basename'];

    //  This is the name of the image file without the extension ("photo")
    $basic_filename = $path_parts['filename'];

    $info_file_path = $folder.'/'.$basic_filename.'.json';

    //  After each image and JSON file have been processed - that is, inserted into WordPress - we
    //  want to move them to the _posted_items folder so they're not re-posted to WordPress next
    //  time this script runs. Moving a file in PHP means renaming it to the path of the place
    //  you want to move it to. 
    
    //  So let's set up that path now. We're not moving anything yet, just creating the path
    //  that the processed file will be moved to.
    $file_posted_path = $settings['posted_files_dir'].'/'.$image_basename;

    //  Now make sure that the JSON file exists before proceeding. If it doesn't exist, move
    //  on without adding anything to WordPress, but display an error.
    if( !file_exists($info_file_path) ) {
      echo '<p><strong>Error:</strong> file ' . $info_file_path . ' does not exist.   Make sure the filename is accurate.</p>';
    }
    else {
      //  The JSON file exists, yay

      //  As above, we're going to set up the path that the JSON file will be
      //  moved to once it's been processed.      
      $info_file_posted_path = $settings['posted_files_dir'].'/'.$basic_filename.'.json';
      
      //  At this point, get the contents of the JSON file in preparation for running
      //  it through insert_image_post().
      $info_file_data = file_get_contents( $info_file_path );
      
      //  You might have noticed that while each post's info is contained in a JSON file, the
      //  data handled by insert_image_post() is an associative array. Here's where that happens.
      
      //  if the post info is not in array format, it's JSON, which
      //  means we have to turn it into an array.
      if( $settings['info_file_type'] != 'array' )
        $info_file_data = json_decode( $info_file_data, true );
      
      //  This is the public image path, which insert_image_post() needs
      //  to find the image it's inserting
      
      $image_url = $settings['public_directory_url'] . $path_parts['basename'];
      
      insert_image_post( $image_url, $info_file_data );

      //  Now we move the posted files to the _posted_items folder    
      rename($file, $file_posted_path);
      rename($info_file_path, $info_file_posted_path);
      
      echo '<p>Moved both '.$image_basename.' and '.$basic_filename.'.json to /files/_posted_items.</p>';
    }
  }
  unset($file);
}



scan_folder( $settings['files_dir']);

?>

<footer>
  <p>This uses <a href="https://github.com/kognise/water.css">kognise’s excellent water.css</a></p>
</footer>


</body>

</html>