=== Article Forge ===
Contributors: borgboy
Tags: articles,hierarchical,document,writing,publish
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.1.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Article Forge WP plugin provides a conducive environment for the writing and publishing of hierarchical prose.

== Description ==

The Article Forge WordPress plugin aims to provide a capable facility to aid in the writing, publishing, and maintenance of hierarchical documents for the web.  In particular are stories, articles, technical documents and periodicals that have titled sections.

Documents are organized such that the head consists of a title and summary (ie. abstract) describing the whole.  Beneath this head are the various sections comprising the document with each section consisting of a heading and content.  The entire document does not need to be assembled before publishing; sections can be published independently of each other.

Many useful features abound through out the editing interface.  The main article edit screen provides a sortable ordered list of the sections with short-cuts to the section and a link to add new sections.  Further, each document has display options that allow the author to customize the look and feel of the published article.  These include:

*  Display the Table of Contents on each page
*  Display the Summary on each page
*  Display the entire document on one page

CSS class tags are exposed for each div in the HTML content providing an easy means for users to specify their own style.  In fact, users are encouraged to make their own copy of the css, js, and HTML under the content directory.  An option is provided under the Settings page to specify the content directory from which files are served.  All .css and .js files under the corresponding directories are automatically included in frontend Article Forge related pages.

The Article Forge plugin is unique in that the concepts of template and content are separated.  Content is injected into the template instead of separate template files being required for each of the different post types, easing the burden on website maintainers.  (See the *Installation* section for details.)

General commentary, upcoming features, and other WordPress related thought can be found at [http://www.bytewisemcu.org/article/article-forge/](http://www.bytewisemcu.org/article/article-forge/ "Article Forge in general").

== Installation ==

Article Forge is simple to install and use with WordPress.  The only requirement is a shift in perspective in how WordPress organizes and generates pages.  The underlying engine for the Article Forge plugin makes a clear distinction between the concept of WordPress templates and that of generated content inside the theme's displayable content area.  Generally speaking, WordPress requires the site maintainer to create separate template pages for each custom post type under the theme's template directory; not the plugin directory.  These templates almost always leave the template's layout intact with changes made only in the content area. Seeking to provide a more practical and persistant approach, the Article Forge plugin provides a shared hook that generates the content for the template.  In this way the user is not required to copy source html from the plugin directory and edit it.

There are various ways to utilize this feature with the simplest approach for those who wish to use Article Forge with other content types (this includes post and custom) is to do it the 'WordPress' way.
*  Copy 'single.php' and 'archive.php' located under your Themes working directory to 'single-articleforge_summary.php' and 'archive-articleforge_summary.php' respectively.
*  Edit these files using the WP built-in editor under the Admin Appearance menu.  Remove the existing post content generation area (usually denoted by `<div id="content>`) and replace it with

     `<?php regwptk_generate_content(); ?>`

The other approach for users who only wish to use Article Forge for site content can modify 'single.php' and 'archive.php' directly while those wishing to use Article Forge with WordPress Posts can copy those files to 'single-post.php' and 'archive-post.php' before editing.  (For those brave souls who are interested in testing a new approach to content generation via plugins with custom post types in WordPress should contact me via <>.)

Article Forge will generate the content based on the post type of the post(s) being displayed and the context of the request (ie. archive, single, search).  If it detects that the post type is not one of its own registered types, it will defer back to the WordPress template engine to generate the page.

Basic CSS is provided for the generated HTML with ample class tags provided for your customization needs.  It is suggested that users copy the contents of the 'default' directory under the Article Forge plugin 'content' directory to a parallel directory under 'content'.  (You can specify that content be served from this directory by editing the appropriate option under the Article Forge settings page.)  Note that any CSS or JS files that you place under the css and js directories are automatically included in Article Forge content pages.

Slugs designating the articles and article areas in the URL are defined in the Article Forge Settings page under the admin area.  They are respectively 'articles' and 'article' by default.  There are many other options avaible that control how the plugin behaves.  You are encouraged to become familiar with these options.

== Frequently Asked Questions ==

= Can I use Article Forge without having to modify my existing template files to support the psuedo WP Registry ToolKit functions? =

Yes, you can.  Copy your existing 'single.php' and 'archive.php' located under your working theme directory to 'single-articleforge_summary' and 'archive-articleforge_summary' respectively.  (I may consider adding a management feature to do this automatically if there is sufficient interest.)  Edit these files using the WP built-in editor under the Admin Appearance menu.  Remove the existing 'content' generation area and replace it with `regwptk_generate_content();` per the installation instructions.

= I really like the Article Forge plugin, but there are some additional features I would like to see.  Where can I make requests? =

Make requests under the Support section of the ArticleForge plugin page under the WordPress.org website.

= I'm having problems with the plugin in my WordPress installation.  I'm ready to leave some nasty feedback.  Where can I explain my problem? =

Enter issues/bugs under the Support section of the ArticleForge plugin page under the WordPress.org website.

== Screenshots ==

1. Screenshot of the plugin edit page
2. Screenshot of the plugin settings page
3. Screenshot of the plugin display page

== Changelog ==

= 1.1.4 =
* Added scroll section into view feature
* Added view_all_sections query arg

= 1.1.3 =
* Added Category and Tag support
* Added WP type preview support to both articles and sections
* Moved to WP macros to render content where appropriate
* Updated Installation instructions
* Create post_name for draft Article Summaries

= 1.1.2 =
* Fix PHP version activation check
* Bump release to bring wp svn back into sync

= 1.1.1 =
* Modified code to support PHP 5.3.x
* Added activation check for PHP 5.3.x

= 1.1.0 =
* Added preview draft support
* Added error harness for Admin
* Removed debug statements

= 1.0.7 =
* Added navigation buttons to Section edit screen
* Corrected for maximum of 10 Article sections
* Added corrections for initial section creation

= 1.0.6 =
* Added Categories and Tags support for Articles
* Assigned specific class to each content target
* Added CSS for each content target

= 1.0.5 =
* Added default CSS for baseline functionality
* Removed all header tags
* Created internal anchors when showing all sections

= 1.0.4 =
* Added shortcode support

= 1.0.3 =
* Fixed 'Invalid Post Type' msg for New Content
* Fixed automatic title propagation in Content Editor

= 1.0.2 =
* Added home page support
* Fixed time of load of Options in Setting module

= 1.0 =
* Official release!
