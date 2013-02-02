# CPT Archives #

A WordPress plugin to manage post type archive titles, descriptions, and permalink slugs from the dashboard.

## Description ##

CPT Archives allows for editing a post type's archive properties by registering a new custom post type called `cpt_archive` that maps directly to a post type archive and provides an interface for easy management. In fact, since it's really nothing more than a CPT, it can be further extended with plugins to add more meta boxes for customizing your archive pages in new and interesting ways.

Another benefit to this approach is that a new "Archive" meta box appears on the *Appearance -> Menus* screen, making it easy to add your post type archives to a nav menu without using a custom link. Even if your archive slug does happen to change, you'll no longer have to update the URL in your menu.

## Installation ##

### Upload ###

1. Download the latest tagged archive (choose the "zip" option).
2. Go to the __Plugins -> Add New__ screen and click the __Upload__ tab.
3. Upload the zipped archive directly.
4. Go to the Plugins screen and click __Activate__.

### Manual ###

1. Download the latest tagged archive (choose the "zip" option).
2. Unzip the archive.
3. Copy the folder to your `/wp-content/plugins/` directory.
4. Go to the Plugins screen and click __Activate__.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git ###

Using git, browse to your `/wp-content/plugins/` directory and clone this repository:

`git clone git@github.com:bradyvercher/wp-cpt-archives.git`

Then go to your Plugins screen and click __Activate__.

## Add Support ##

To register custom archive support for an existing post type, add a quick snippet to your theme's `functions.php` file or in a custom plugin like this:

```php
function my_cpt_archives_init() {
     add_post_type_support( 'my_post_type', 'archive' );
}
add_action( 'init', 'my_cpt_archives_init' );
```

This automatically adds a submenu in the post type's menu that points directly to the archive edit screen. Behind the scenes, when you register support for an archive like this, a new `cpt_archive` post is created and tied to the post type, so you don't have to do anything else.

If your post type displays as a submenu item itself, you'll probably need to register your own submenu item to edit the archive. A few filters are sprinkled through the plugin to allow changing how you can access these new archive posts.

## Template Tags ##

__`post_type_archive_title()`__

Use this standard template tag for displaying the archive title. The plugin filters the output to use whatever title you enter in the dashboard.

__`post_type_archive_description( $before = '', $after = '' );`__

This is a custom template tag for displaying the content from the editor in the dashboard. The parameters are optional, but let you specific HTML or text to display before and after the description if one exists. If a description doesn't exist, then nothing will be displayed.

## Credits ##

Built by [Brady Vercher](https://twitter.com/bradyvercher)  
Copyright 2012 [Blazer Six, Inc.](http://www.blazersix.com/)  