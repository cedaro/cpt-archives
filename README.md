# CPT Archives

A WordPress plugin to manage post type archive titles, descriptions, and permalink slugs from the dashboard.

__Contributors:__ [Brady Vercher](https://twitter.com/bradyvercher)  
__Requires:__ WordPress 4.0+ & PHP 5.4+  
__License:__ [GPL-2.0+](http://www.gnu.org/licenses/gpl-2.0.html)


## Description

*CPT Archives* allows for editing a post type's archive properties by registering a new `cpt_archive` custom post type that's connected to the post type (that's a mind bender). In fact, since it's really nothing more than a CPT, it can be further extended with plugins to add meta boxes for customizing your archive pages in new and interesting ways. By default, archive titles, descriptions and permalinks can be managed through a familiar interface.

Another benefit is that a new "Archive" section appears on the *Appearance &rarr; Menus* screen, making it easy to add your post type archives to a menu without using a custom link. Even if your archive slug is changed, you won't have to update the URL in your menu.


## Usage

To register archive support for an existing post type, add a quick snippet to your theme's `functions.php` file or in a custom plugin like this:

```php
add_action( 'init', function() {
	add_post_type_support( 'my_post_type', 'archive' );
} );
```

This automatically adds a submenu in the post type's menu that points directly to the archive edit screen. Behind the scenes, when you register support for an archive this way, a new `cpt_archive` post is created and connected to the post type, so you don't have to do anything else.


### Advanced Registration

For more control, an alternative API is available for registering archives:

```php
add_action( 'init', function() {
	if ( empty( $GLOBALS['cptarchives'] ) ) {
		return;
	}

	$GLOBALS['cptarchives']->register_archive( 'my_post_type', array(
		'customize_rewrites' => true,
		'show_in_menu'       => true,
		'supports'           => array( 'title', 'editor' ),
	) );
} );
```

<table><caption><h3>Archive Registration Arguments</strong></h3>
  <thead>
    <tr>
      <th>Argument</th>
      <th>Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong><code>customize_rewrites</code></strong></td>
      <td>Whether the post type's rewrites can be customized. Defaults to <code>true</code>. Accepts <code>false</code>, <code>'archives'</code>, and <code>'posts'</code>.</td>
    </tr>
    <tr>
      <td><strong><code>show_in_menu</code></strong></td>
      <td>Whether the archive should be added to the admin menu. Defaults to <code>true</code>. Also accepts a top level menu item id.</td>
    </tr>
    <tr>
      <td><strong><code>supports</code></strong></td>
      <td>A list of <a href="http://codex.wordpress.org/Function_Reference/add_post_type_support">post type features</a> that should be enabled on the archive edit screen.</td>
    </tr>
  </tbody>
</table>


## Installation

### Upload

1. Download the [latest release](https://github.com/cedaro/cpt-archives/archive/master.zip) from GitHub.
2. Go to the _Plugins &rarr; Add New_ screen in your WordPress admin panel and click the __Upload__ button at the top next to the "Add Plugins" title.
3. Upload the zipped archive.
4. Click the __Activate Plugin__ link after installation completes.

### Manual

1. Download the [latest release](https://github.com/cedaro/cpt-archives/archive/master.zip) from GitHub.
2. Unzip the archive.
3. Copy the folder to `/wp-content/plugins/`.
4. Go to the _Plugins &rarr; Installed Plugins_ screen in your WordPress admin panel and click the __Activate__ link under the _CPT Archives_ item.

Read the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git

Clone this repository in `/wp-content/plugins/`:

`git clone git@github.com:cedaro/cpt-archives.git`

Then go to the _Plugins &rarr; Installed Plugins_ screen in your WordPress admin panel and click the __Activate__ link under the _CPT Archives_ item.
