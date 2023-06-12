# PostalService

PostalService is a PHP script that inserts images and posts into WordPress.

## Overview

What you do: 

- Upload two files to PostalService's `files` folder: an image that you want to make the featured image for that post, and a .json file containing the post's content. This .json file has to have the same name as its associated image. It contains all post content and attributes (category, tags, date, etc). You can even specify custom fields. See [JSON file structure](#json-file-structure). 
- When you're ready to create a WordPress post, load the script file.

PostalService then does the following:

- Scans the "files" folder for any GIF, JPG, or PNG files (you can add any extensions you want, though)
- For each file it comes across, it looks for a corresponding .json file. PostalService reads this file and creates a new post in WordPress.
- PostalService then [sideloads](https://developer.wordpress.org/reference/functions/media_sideload_image/) the image to WordPress, and attaches it to the post it just created, specifying it as a featured image.

***Notes:***
- If you have some-post.jpg and some-post.png, and an associated some-post.json file, that post will be created twice: once for each image.


### System requirements
- This requires WordPress 4.8.0 or later.
- I've tested it on WordPress 6.2. I don't have any reason to think there are problems on earlier versions, but I haven't tested them.



## Setup

### Where everything goes

```
..📁plugins
   └ 📁PostalService
      └ 📁files
         └ 🖼a-post.jpg
         └ 📄a-post.json
         └ 🖼another-post.png
         └ 📄another-post.json
         └ 📁_posted_items
```

1. In your WordPress plugins folder, create a folder for PostalService. I'll refer to it here as the "PostalService folder", but it doesn't need to be called "postalservice" and, for security reasons, probably shouldn't be. If you change it, however, you need to specify what the name is in the script (see Configuration). (PostalService can technically be anywhere on your server; the one place you should **not** put it is in with the core WordPress installation files.)
2. **Rename the script**, again for security reasons. I suggest something diabolically random, like an MD5 hash.
3. In the PostalService folder, create a folder named "files". This is where all your files go before they're posted.
4. In the PostalService/files folder, create a subfolder named "_posted_items". This is where your files go after they're posted.



### Configuration
In the "postal.php" file, scroll down to the SETUP section.

- `$ps__wp_root`: this is the path from the root of your server. It is set to `$_SERVER['DOCUMENT_ROOT']` by default. You probably won't need to change this.
- `$ps__the_main_folder`: The name of whatever folder that postal.php (or whatever you've named the script) is located in.
- `$ps__public_basefolder`: The public URL of the folder that the PostalService folder is contained in. This would be something like "https://www.example.com/wp-content/plugins/"
- `$ps__timezone`: Your local timezone. If your website is hosted on a server in a different timezone, your posts might be assigned a different time. If that time is in the future, your posts will be scheduled rather than posted right away.
- `$ps__image_types`: The file extensions that PostalService looks for.
- `$ps__default_post_status`: The status that any newly created WordPress posts will have. This can be [any valid WordPress post status](https://wordpress.org/documentation/article/post-status/). It's set to "draft" by default; set it to "publish" when you're ready.

You can have PostalService generate any WordPress content type; it's not limited to posts. However, you'll need to include a `post_type` line in each .json file:

```
  "post_type": "photo",
```

Currently, if you want PostalService to default to a particular content type, you'll need to modify the `wp_insert_post()` call in `insert_image_post()`. In the future, I'll [make that easier](https://github.com/csilverman/postalservice/issues/3).


### JSON file structure

The .json files that contain post info will look something like this:

```
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
```

You don't have to specify every possible post attribute in a .json file. In the above example, note that no post date or author was specified. PostalService has default values for these (the current date and the admin user, respectively).

Note that PostalService doesn't recognize all of [the parameters allowable by `$postarr`](https://developer.wordpress.org/reference/functions/wp_insert_post/#parameters), just the following:

- `post_title` - *defaults to "title"*
- `post_content`
- `post_date` - *defaults to current date*
- `post_author` - *defaults to user ID `1`*
- `post_type` - *defaults to `post`*
- `post_status` - *defaults to whatever value you've set in the `post_status` configuration*
- `post_category`
- `post_tags` - *note that the official WordPress array key name is `tags_input`*

In the future, I'll add support for all post parameters.

You can also, if you need to, specify post content in an PHP array rather than JSON format. If you're going to do that, make sure you set `$ps__info_file_type` to "array". The file extension for content files still has to be .json, however. (I agree this is inelegant; at this point, I'm looking at arrays as an edge case.)



## Important Notes


### Images are required for posts

In its current state, PostalService requires that every blogpost have an associated feature image. PostalService scans the `files` folder looking for images, and then checks for an associated JSON file with the post information. If a post doesn't have an image—just a JSON file, with no associated image file—PostalService won't find it, and no post will be created.

In retrospect, I should have designed PostalService to look for JSON files *first*, and then check to see if there's an associated image that should be treated as a featured image. I initially built PostalService to update an imageblog, so images were foremost in my mind. At some point, I'll update this so images aren't required.


### PostalService isn't designed to handle lots of images

If you use this to upload a lot of images, you may get a 500 error. In my experience, the danger zone is around 60-70 files, but this likely varies depending on server setup, available memory, how big the files are, etc.

Also in my experience, this is more of a speed bump than anything else. Everything processed so far will have been posted to WordPress, and PostalService moves uploaded files into the _posted_items folder as it uploads them, so it won't re-upload anything; just refresh the page to restart the process and continue uploading.

Keep in mind, though, that my use case for this was to set up WordPress posts for a daily image, so this is mainly for uploading only a few images at most. Bulk-uploading large files is not really what I designed this for. If you have any thoughts on how to improve this, [I would love to know about them](mailto:chris@csilverman.com). I might, at some point, set up an AJAX-based approach where another script serves this one each image at a time, rather than having one script take on 200 images. But maybe there's a better way? Anyway, you've been warned. 


### iOS Shortcuts

When I built PostalService, my intention was to use it as one half of a workflow that let me post to my WordPress blog from my iPhone. I'm aware that there is an official WordPress iOS app, and that this app includes a Shortcut action. I was not impressed by either of those, in particular  the Shortcut action, which—in the course of posting a single post—sent so many requests to my blog that my web host mistook it for an attack and blocked it.

So I built a Shortcut that does the following:

- prompts you to select an image from your photo library
- generates a JSON file
- uploads both files to the PostalService directory
- calls the PostalService script with an Open URL action

iOS Shortcuts does not currently include an FTP action, so you'll need to use a third-party app. I recommend [Secure Terminal](https://apps.apple.com/us/app/secure-terminal/id1463284695). It's a one-time purchase, and includes a Save File action that uploads a file to a server.

A few things you should know:

- Make sure the path it's uploading to is valid/correct. On DreamHost, that'll look something like /home/dh_XXXXXX/your.domain.com/wp-content/plugins/postalservice/files/. File Path Destination has to include the filename; if you just pass it the directory path with no filename, it'll fail.
- The SFTP user account needs shell access. On DreamHost, SSH is off by default for new users. You'll need to turn this on in the DreamHost admin panel before you can upload files via Secure Terminal.
- I've noticed that Secure Terminal can't handle very large files. I'm not sure what the exact number is, but anything over a couple MB seems to be problematic. You might need to resize images you're uploading to make them smaller.


