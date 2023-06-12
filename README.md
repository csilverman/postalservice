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

..📁plugins
   └ 📁PostalService
      └ 📁files
         └ 🖼a-post.jpg
         └ 📄a-post.json
         └ 🖼another-post.png
         └ 📄another-post.json
         └ 📁_posted_items

1. In your WordPress plugins folder, create a folder for PostalService. I'll refer to it here as the "PostalService folder", but it doesn't need to be called "postalservice" and, for security reasons, probably shouldn't be. If you change it, however, you need to specify what the name is in the script (see Configuration). (PostalService can technically be anywhere on your server; the one place you should **not** put it is in with the core WordPress installation files.)
2. **Rename the script**, again for security reasons I suggest something diabolically random, like an MD5 hash.
3. In the PostalService folder, create a folder named "files". This is where



### Configuration
In the "postal.php" file, scroll down to the SETUP section.

- `$ps__wp_root`: this is the path from the root of your server. It is set to `$_SERVER['DOCUMENT_ROOT']` by default. You probably won't need to change this.
- `$ps__the_main_folder`: The name of whatever folder that postal.php (or whatever you've named the script) is located in.
- `$ps__public_basefolder`: The public URL of the folder that the PostalService folder is contained in. This would be something like "https://www.example.com/wp-content/plugins/"
- `$ps__timezone`: Your local timezone. If your website is hosted on a server in a different timezone, your posts might be assigned a different time. If that time is in the future, your posts will be scheduled rather than posted right away.
- `$ps__image_types`: The file extensions that PostalService looks for.
- `$ps__default_post_status`: The status that any newly created WordPress posts will have. This can be [any valid WordPress post status](https://wordpress.org/documentation/article/post-status/). It's set to "draft" by default; set it to "publish" when you're ready.

/// PERMISSIONS: what do these need to be?


### JSON file structure




## Important Notes



via [Secure Terminal](https://apps.apple.com/us/app/secure-terminal/id1463284695)



### Images are required for posts

In its current state, PostalService requires that every blogpost have an associated feature image. PostalService scans the `files` folder looking for images, and then checks for an associated JSON file with the post information. If a post doesn't have an image—just a JSON file, with no associated image file—PostalService won't find it, and no post will be created.

In retrospect, I should have designed PostalService to look for JSON files *first*, and then check to see if there's an associated image that should be treated as a featured image. I initially built PostalService to update an imageblog, so images were foremost in my mind. At some point, I'll update this so images aren't required.


### PostalService isn't designed to handle lots of images

If you use this to upload a lot of images, you may get a 500 error. In my experience, the danger zone is around 60-70 files, but this likely varies depending on server setup, available memory, how big the files are, etc.

Also in my experience, this is more of a speed bump than anything else. Everything processed so far will have been posted to WordPress, and PostalService moves uploaded files into the _posted_items folder as it uploads them, so it won't re-upload anything; just refresh the page to restart the process and continue uploading.

Keep in mind, though, that my use case for this was to set up WordPress posts for a daily image, so this is mainly for uploading only a few images at most. Bulk-uploading large files is not really what I designed this for. If you have any thoughts on how to improve this, [I would love to know about them](mailto:chris@csilverman.com). I might, at some point, set up an AJAX-based approach where another script serves this one each image at a time, rather than having one script take on 200 images. But maybe there's a better way? Anyway, you've been warned. 


### iOS Shortcuts

When I built PostalService, my intention was to use it as one half of a workflow that let me post to my WordPress blog from my iPhone. I'm aware that there is an official WordPress iOS app, and that this app includes a Shortcut action. I was not impressed by either of those, in particular  the Shortcut action, which—in the course of posting a single post—sent so many requests to my blog that my web host mistook it for an attack and blocked it.

 with iOS Shortcuts in mind

Some notes on the Secure Terminal action

If you're using this in conjunction with iOS Shortcuts

- Make sure the File Path Destination includes the filename. If it's just the directory path with no filename, it'll fail.
- Make sure the path it's uploading to is valid/correct. On Dreamiest, that'll look something like /home/dh_XXXXXX/your.domain.com/wp-content/plugins/postalservice/files/. Does the folder it's being uploaded to actually exist?
- **The SFTP user account needs shell access.** On DreamHost, SSH is off by default for new users. You'll need to turn this on in the DreamHost admin panel before you can upload files via Secure Terminal.
- I've noticed that Secure Terminal can't handle very large files. I'm not sure what the exact number is, but anything over a couple MB seems to be problematic. You might need to resize images you're uploading to make them smaller.

