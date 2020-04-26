Block Plus (module for Omeka S)
===============================

[Block Plus ] is a module for [Omeka S] that adds some new blocks for the static
pages and improves some of the existing ones.


Installation
------------

First, install the optional module [Generic].

The module uses external js libraries for some blocks, so use the release zip to
install it, or use and init the source.

* From the zip

Download the last release [`BlockPlus.zip`] from the list of releases (the
master does not contain the dependency), and uncompress it in the `modules`
directory.

* From the source and for development:

If the module was installed from the source, rename the name of the folder of
the module to `BlockPlus`, and go to the root module, and run:

```
    composer install
```

The next times:

```
    composer update
```

Then install it like any other Omeka module.

See general end user documentation for [Installing a module].


Usage
-----

Select them in the view "Page edit". You may theme them too: copy the block
templates that are in `view/common/block-layout/` in the same place of your
theme.

### Page Metadata

Allow to add a type to the page, so it’s simpler to have different templates for
different pages. It allows in particular to create exhibits inside a site,
without creating a new site.

You can add new types in the settings of the site. Then, in your theme, you can
get the page type `$pageType = $this->pageMetadata('type');` and many other data
about the page. If the page is an exhibit, it is possible to build a specific
navigation menu of this exhibit too, like in the site [Fondation de la Maison de Salins].

To manage multiple types, it’s generally required to edit the template "view/omeka/site/page/show.phtml"
of your theme and to add a check:

```php
$pageMetadata = $plugins->has('pageMetadata') ? $plugins->get('pageMetadata') : null;
$type = $pageMetadata('type') ?: null;
switch ($type):
    case 'home':
        $class = 'home';
        // Specific html code…
        break;
    case 'exhibit':
        $class = 'exhibit';
        // Specific html code…
        break;
    case 'exhibit_page':
        $class = 'exhibit-page';
        // Specific html code…
        break;
    case 'simple':
        $class = 'simple-page';
        // Specific html code…
        break;
    default:
        // Generic html code…
        $class = 'page';
        break;
endswitch;
$this->htmlElement('body')->appendAttribute('class', $class);
// …
```

### Assets

Display a list of assets with optional urls and labels. It’s useful to build a
block of partners, for example. The assets are not required to be filled, so it
allow to display any list of contents.

### Simple block

A simple block allow to display a template from the theme. It may be used for a
static html content, like a list of partners, or a complex layout, since any
Omeka feature is available in a view.

### Mirror page

Allow to use a page as a block, so the same page can be use in multiple sites,
for example the page "About" or "Privacy". Of course, the page is a standard
page and can be more complex with multiple blocks. May be fun.
This is an equivalent for the [shortcode as a page] in [Omeka Classic] too.

### Resource with html

Simplify the display of a media on the left or the right (see [user guide]). It
is the same block that existed in [Omeka Classic] [Exhibit `file-text`].

### External content

Similar to media with html, but to display an external asset that is not a
resource neither an asset file, so currently not manageable inside Omeka. It may
be used to display a html text with a video on the home page.

### Search form

Include a specific search form in a specific page. The default query may be
adapted to the page via the theme.

### Search and results

Create a full search page with a simple or complex form and the results on the
same page. All options should be managed via the theme. To replace item/browse,
item-set/browse and even media/browse, you may need to set the page as default
action for the search in default template `common/search-form`.

### Separator

Allow to set a div with a specific class between two blocks. May be useful to
fix some css issues, or to increase space between some blocks.

### Column

Allow to group blocks in one or multiple columns. Columns can be nested. The
css should be prepared in the theme to managed them. By default, only a simple
`aside` column of 30% is available with class `column align-right` (or left).

### Improvements for Browse preview, Html, Item metadata, Item showcase, List of sites, Page title, Table of contents

Allow to use a specific template for some blocks, so it’s possible to display
these blocks differently in the same page or on different pages. An heading is
added too. For the table of contents, the possibility to display the table from
the root is added too.

Furthermore, the block Browse preview has new fields to display sort headings
and pagination, so it's now possible to have a specific list of items, like the
main browse view. It has another template for gallery display with a quick
viewer too (browse-preview-gallery). This one has a specific option to add to
the query to display thumbnails as square or medium: `thumbnail_size=medium`.

**Warning**

When a block allows to select a template, the filename must start with the same
string than the original template, for example "table-of-contents-pages.phtml"
for the block `TableOfContents`.

Furthermore, it should exists in a module or in the current theme. Thereby, when
the module or the theme that have this template are replaced, you have to check
the pages that use it.


TODO
----

- Merge some similar blocks into a main block (with automatic upgrade).
- Integrates Shortcodes


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitHub.


License
-------

### Module

This module is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.

### Gallery of images

See MIT license here: http://tympanus.net/codrops/licensing/
The original template was fixed by KevinMwangi and updated for newer version of
components (modernizr, smartresize, imagesloaded).


Copyright
---------

* Copyright Daniel Berthereau, 2018-2019 (see [Daniel-KM] on GitHub)
* Copyright Codrops, 2013 ([image gallery], see vendor/ for more infos)


[Block Plus]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus
[Omeka S]: https://omeka.org/s
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[shortcode as a page]: https://github.com/omeka/plugin-SimplePages/pull/24
[Omeka Classic]: https://omeka.org/classic
[Exhibit `file-text`]: https://omeka.org/classic/docs/Plugins/ExhibitBuilder
[Fondation de la Maison de Salins]: https://collections.maison-salins.fr
[user guide]: https://omeka.org/s/docs/user-manual/sites/site_pages/#media
[`BlockPlus.zip`]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus/releases
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://http://opensource.org/licenses/MIT
[image Gallery]: https://github.com/codrops/ThumbnailGridExpandingPreview
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
