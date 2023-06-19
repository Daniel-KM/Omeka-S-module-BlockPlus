Block Plus (module for Omeka S)
===============================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Block Plus] is a module for [Omeka S] that adds some new blocks for the static
pages and improves some of the existing ones: image gallery, D3 graph, mirror
page, search form, assets, item set showcase, exhibits, footnotes, etc.

Furthermore, each block can use multiple templates, so it's possible to theme a
block differently in different pages.

Some resource page blocks are implemented too.


Installation
------------

First, install the optional module [Generic] if wanted.

The module uses external js libraries for some blocks, so use the release zip to
install it, or use and init the source.

* From the zip

Download the last release [BlockPlus.zip] from the list of releases (the master
does not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development:

If the module was installed from the source, rename the name of the folder of
the module to `BlockPlus`, and go to the root module, and run:

```sh
composer install --no-dev
```

Then install it like any other Omeka module.

See general end user documentation for [Installing a module] and follow the
config instructions.


Usage
-----

Select them in the view "Page edit". You may theme them too: copy the block
templates that are in `view/common/block-layout/` in the same place of your
theme.

### Improvements in all blocks

The core blocks (Browse preview, Html, Item metadata, Item showcase, List of pages,
List of sites, Page title, Table of contents) are improved with two new options:

#### Heading

Most of the blocks have an option to set a title.

#### Templates

Most of block have an option to set a specific template, so it’s possible to
display these blocks differently in the same page or on different pages.

**Warning**

When a block allows to select a template, the filename must start with the same
string than the original template, for example "table-of-contents-pages.phtml"
for the block "TableOfContents".

Furthermore, it should exists in a module or in the current theme. When the
template is missing, for example when switching into another theme, the default
of the template is used. Thereby, when the module or the theme that has this
template is replaced, you  have to check the pages that use it.

### New blocks and specific improvements

#### Asset (improvement)

Since the integration of "asset" in Omeka 3.1, this block is an improved version
of the [core block "asset"]. It can list assets with optional link to pages,
labels and caption. The assets are not required to be filled, so it allow to
display any list of contents.

Unlike the upstream version, it has a specific `class` option at asset level and
supports templates. Some templates are available: "asset-block", "asset-hero-bootstrap",
"asset-left-right" and "asset-partners".

#### Block Metadata

This block provides the same information than the view helper `pageMetadata()`
(see below), but from a block. It is usefull with the simple block to extract
params, or to get some informations about the page from anywhere in the theme.

```php
// A simple check is done to make the theme more generic.
$blockMetadata = $plugins->has('blockMetadata') ? $plugins->get('blockMetadata') : null;
if ($blockMetadata):
    $data = $blockMetadata('params_key_value');
endif;
```

### Browse preview (improvements)

The block Browse preview has new fields to display sort headings and pagination,
so it's now possible to have a specific list of items, like the main browse view.

It has some specific templates too:
- simple carousel ("browse-preview-carousel"): this is an upgrade of the plugin
  [Shortcode Carousel] for [Omeka Classic].
- gallery display with a quick viewer too ("browse-preview-gallery"). This one
  has a specific option to add to the query to display thumbnails as square or
  medium: `thumbnail_size=medium`. You can see an example on the site [Ontologie du christianisme médiéval en images],
  from French [Institut national de l’histoire de l’art].

To use them, simply select the wanted template:

![browse-preview-carousel](https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/raw/master/data/images/browse-preview-carousel.png)

#### D3 Graph

The D3 graph adds the [D3 library] to display relations between items in a graph:
links between subjects and objects, links between items and item sets, etc.
An example of use can be seen on the digital library of the [Fondation de la Maison de Salins].

#### External content

Similar to media with html, but to display an external asset that is not a
resource neither an asset file, so currently not manageable inside Omeka. It may
be used to display a html text with a video on the home page.

#### HTML (improvements)

Two new options are added in main settings:

- Display the html field as a document, that is a lot easier when editing long
  articles. Furthermore, the field can be maximized.
- Use the default or advanced toolbar. The advanced toolbar is the CKEditor one
  and contains more possibilities to edit advanced text.

Furthermore, it is possible to add footnotes inside each html field.

#### Item Set showcase

This is similar to the block Item Showcase, but for item sets.

#### Mirror page

Allow to use a page as a block, so the same page can be use in multiple sites,
for example the page "About" or "Privacy". Of course, the page is a standard
page and can be more complex with multiple blocks. May be fun.
This is an equivalent for the [shortcode as a page] in [Omeka Classic] too.

### Page date

Display the date of the creation and/or modification of the current page.
See Omeka issue [#1706].

#### Redirect to URL

Allow to redirect the page to another page, inside or outside Omeka. It is useful
for hard coded links in the footer, to keep track of some clicks, to use the page
item/browse as a the home page, or some other use cases.

#### Resource with html

Simplify the display of a media on the left or the right (see [user guide]). It
is the same block that existed in [Omeka Classic] [Exhibit `file-text`].

#### Search form

Include a specific search form in a specific page. The default query may be
adapted to the page via the theme.

#### Search form and results

Create a full search page with a simple or complex form and the results on the
same page. All options should be managed via the theme. To replace item/browse,
item-set/browse and even media/browse, you may need to set the page as default
action for the search in default template `common/search-form`.

#### Separator

Allow to set a div with a specific class between two blocks. May be useful to
fix some css issues, or to increase space between some blocks.

#### Separator/Division

Allow to wrap a block or multiple block with a `div`. in particular to create
columns. Divisions can be nested. The css should be prepared in the theme to
managed them. By default, only a simple `aside` column of 30% is available with
class `column align-right` (or left).

#### Simple block

A simple block allow to display a template from the theme. It may be used for a
static html content, like a list of partners, or a complex layout, since any
Omeka feature is available in a view.

##### Tree view

An example layout is provided to display a dynamic tree view from a tsv/csv
file. The file should be one value by a row, with the offset matching the depth:

```
Asia
        Japan
                Tokyo
Europe
        France
                Paris
        Italy
                Roma
                Florence
```

##### Glossary

A second example is a template to create a standard glossary (definition list):

```
alpha = First letter of Greek alphabet
beta = Second letter of Greek alphabet
```

The glossary can be created with html too with the block `html` and template `html-glossary`.
In that case, set a list of term and definition separated by an empty line:

```
alpha
definition of alpha

beta
definition of beta
definition of beta continued…
```

To insert a line, it is recommended to set the cursor at the start of the line.
It will avoid possible issues.

#### Showcase

This is a showcase for any resource, site, page, asset or url. It's an improved
and genericized version of item showcase.

#### Table of contents (improvement)

The table can be displayed from the root if wanted.

#### Twitter

Display the last messages from an account on [Twitter]. To use it, you may use
your own developer bearer token, else the module will try to use an anonymous
one.

Technical note: Since December 2020, it is required [to get a dev account] to
fetch messages of a user, because Twitter disabled any standard html endpoint
(see [this issue on StackOverflow]). If you use a dev account, it is not
necessarily the one of the thread to follow.

Nevertheless, it is still possible to fetch them with an anonymous bearer token.
To get it, check the urls in the dev tools of your browser in the "network" tab.
The token is set in the header of the requests to https://api.twitter.com with
the key `Authorization`, for example `AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA`.
This token will be available for the next two years, _or less_. Anyway, the
module fetches it automagically. Nevertheless, it uses some hard coded urls that
may change.

Check how to generate a [bear token]. You need to create an app in your account
too. If you can't, try to check the option to use the Api version 1.1.

In all cases, there is a [rate limit], but generally largely enough for a common
digital library.

### Resource page blocks (Omeka S v4)

#### Block simple

A block to customize in theme.

#### Buttons Previous/Next

This block requires the module [Easy Admin].

Allow to display buttons previous resource and next resource in the list. The
list is built from the user last browse query, else from natural order. The
default order without previous browse or search can be set in site settings for
items and item sets. For media, the order is defined in item.

It can be used as a theme helper too (see below).

### Theme view helpers

#### Page Metadata

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
// A simple check is done to make the theme more generic.
$pageMetadata = $plugins->has('pageMetadata') ? $plugins->get('pageMetadata') : null;
if ($pageMetadata):
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
endif;
```

#### Pages Metadata

This view helper allows to get the data of all pages of the same type in the
current site. For example, you can get all "exhibit_page". Multiple types can be
retrieved at once.

```php
// A simple check is done to make the theme more generic.
$pagesMetadata = $plugins->has('pagesMetadata') ? $plugins->get('pagesMetadata') : null;
if ($pagesMetadata):
    $data = $pagesMetadata('exhibit_page');
endif;
```

#### Breadcrumbs

A breadcrumb may be added on resources pages via the command `echo $this->breadcrumbs();`.
The default template is `common/breadcrumbs.phtml`, so the breadcrumb can be
themed. Some options are available too.
By default, the breadcrumbs for an item use the first item set as the parent
crumb. The first item set is the item set with the smallest id. If you want to
use another item set, set it as resource in the property that is set in the main
settings, or in the options of the view helper.

#### Buttons Previous/Next

These helpers require the module [Easy Admin].

To use it, add the following code in the item, item set, or media show page:

```php
<?php
$plugins = $this->getHelperPluginManager();
$previousNext = $plugins->has('previousNext') ? $plugins->get('previousNext') : null;
?>

<?= $previousNext ? $previousNext($resource) : '' ?>
```

Two other helpers can be used to manually build the html code:

```php
<?php
$plugins = $this->getHelperPluginManager();
$hasPreviousNext = $plugins->has('previousNext');
?>

<?php if ($hasPreviousNext): ?>
<div class="previous-next-items">
    <?php if ($previous = $this->previousResource($resource)): ?>
    <?= $previous->link($translate('Previous item'), null, ['class' => 'previous-item']) ?>
    <?php endif; ?>
    <?php if ($next = $this->nextResource($resource)): ?>
    <?= $next->link($translate('Next item'), null, ['class' => 'next-item']) ?>
    <?php endif; ?>
</div>
<?php endif; ?>
```

#### Last browse page

Allow to go back to the last list of results in order to browse inside item
sets, items or media after a search without losing the search results. It can be
appended automatically with helper "previousNext()" and option "back" set to
true.


TODO
----

- [ ] Merge more similar blocks into a main block (with automatic upgrade).
- [x] Integrate Shortcodes (module [Shortcode])
- [x] Integrate Menu (module [Menu])
- [ ] Merge module Menu inside BlockPlus?
- [ ] Normalize breadcrumbs.
- [ ] Integrate attachments for block Showcase
- [ ] Integrate sidebar forms for block Showcase
- [ ] Auto-create asset when image is uploaded in a Html field.
- [ ] Update site of mirror page to get good url for $resource->siteUrl().
- [ ] Merge PageDate with upstream PageDateTime.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

### Module

This module is published under the [CeCILL v2.1] license, compatible with
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

### Carousel

[MIT] license.

### CKEditor 4

One of the CKEditor ones [GPL].

### CKEditor 4 footnotes

One of the CKEditor ones [GPL]. See [CKEditor-Footnotes].

### D3

[ISC License] (equivalent to [MIT])

### Gallery of images

See [MIT] license here: http://tympanus.net/codrops/licensing/
The original template was fixed by KevinMwangi and updated for newer version of
components (modernizr, smartresize, imagesloaded).


Copyright
---------

* Copyright Daniel Berthereau, 2018-2023 (see [Daniel-KM] on GitLab)
* Copyright Codrops, 2013 ([image gallery], see vendor/ for more infos)
* Copyright Andy Kirk, 2014-2021 (See https://github.com/andykirk)
* Copyright Jan Sorgalla, 2014 (See http://sorgalla.com/jcarousel)


[Block Plus]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus
[Omeka S]: https://omeka.org/s
[Installing a module]: https://omeka.org/s/docs/user-manual/modules/#installing-modules
[shortcode as a page]: https://github.com/omeka/plugin-SimplePages/pull/24
[Omeka Classic]: https://omeka.org/classic
[Exhibit `file-text`]: https://omeka.org/classic/docs/Plugins/ExhibitBuilder
[Fondation de la Maison de Salins]: https://collections.maison-salins.fr
[D3 library]: https://d3js.org
[Fondation de la Maison de Salins]: https://cooparchives.maison-salins.fr/s/fr/page/carte-heuristique
[#1706]: https://github.com/omeka/omeka-s/issues/1706
[Twitter]: https://twitter.com
[Shortcode Carousel]: https://github.com/omeka/plugin-ShortcodeCarousel
[CKEditor Footnotes]: https://github.com/andykirk/CKEditorFootnotes
[to get a dev account]: https://developer.twitter.com/en/apply-for-access
[this issue on StackOverflow]: https://stackoverflow.com/questions/65403350/how-can-i-scrape-twitter-now-that-they-require-javascript
[bear token]: https://developer.twitter.com/en/docs/authentication/oauth-2-0/bearer-tokens
[rate limit]: https://developer.twitter.com/en/docs/twitter-api/rate-limits#table
[Block Plus: Twitter]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlusTwitter
[core block "asset"]: https://omeka.org/s/docs/user-manual/sites/site_pages/#asset
[Ontologie du christianisme médiéval en images]: https://omci.inha.fr/s/ocmi/page/images
[Institut national de l’histoire de l’art]: https://www.inha.fr
[Menu]: https://gitlab.com/Daniel-KM/Omeka-S-module-Menu
[Shortcode]: https://gitlab.com/Daniel-KM/Omeka-S-module-Shortcode
[user guide]: https://omeka.org/s/docs/user-manual/sites/site_pages/#media
[BlockPlus.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/releases
[Easy Admin]: https://gitlab.com/Daniel-KM/Omeka-S-module-EasyAdmin
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://opensource.org/licenses/MIT
[ISC License]: https://github.com/d3/d3/blob/main/LICENSE
[image Gallery]: https://github.com/codrops/ThumbnailGridExpandingPreview
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
