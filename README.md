# postalservice

PostalService is a PHP script that inserts images and posts into WordPress.

## Setup

### Where everything goes
- In your WordPress plugins folder, create a folder for PostalService. It doesn't need to be called "postalservice" and, for security reasons, probably shouldn't be. If you change it, however, you need to specify what the name is in the script (see Configuration).
- In the PostalService folder ///

### Configuration
In the "postal.php" file, scroll down to the SETUP section.

- `$ps__wp_root`: this is the path from the root of your server. It is set to `$_SERVER['DOCUMENT_ROOT']` by default. You probably won't need to change this.
