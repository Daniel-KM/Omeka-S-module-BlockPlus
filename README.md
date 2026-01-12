Block Plus (module for Omeka S)
===============================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Block Plus] is a module for [Omeka S] that adds page models and new blocks for
the editorial pages and improves some of the existing ones: image gallery,
D3 graph, mirror page, search form, item set showcase, exhibits, footnotes, etc.
Some resource page blocks are implemented too: previous/next resource, section,
etc.

Another feature is the possibility to pre-configure page models and list of
blocks, so it is easier to build a new page. The page models and blocks groups
can be defined via a simple click from an existing page.

**Warning**: Since Omeka S v4.1, many of the features implemented some years ago
were re-implemented in core. The migration from old blocks of the module before
version 3.4.22 to new blocks may require some manual updates of the themes when
they were customized. See below for migration. There is no issue for a new
install.

**Note**: Since version 3.4.44, the **Breadcrumbs** feature (view helper, site
page block, and resource page block) has been moved to module [Menu]. If you
were using breadcrumbs, please ensure the Menu module (at least version 3.4.12)
is installed and updated. Settings will be automatically migrated when you
upgrade the Menu module.


Installation
------------

See general end user documentation for [installing a module].

This module requires the module [Common], that should be installed first.

The module uses external js libraries for some blocks, so use the release zip to
install it, or use and init the source.

* From the zip

Download the last release [BlockPlus.zip] from the list of releases (the master
does not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `BlockPlus`, and go to the root module, and run:

```sh
composer install --no-dev
```

Then install it like any other Omeka module and follow the config instructions.

* For test

The module includes a comprehensive test suite with unit and functional tests.
Run them from the root of Omeka:

```sh
vendor/bin/phpunit -c modules/BlockPlus/phpunit.xml --testdox
```


Usage since version 3.4.22/23 for Omeka S v4.1
----------------------------------------------

### Pre-configuration of page models and list of blocks

When creating a new page, a option allows to select a page model or a blocks
group. When editing an existing page, the button "Add block group plus" allows
to load the same list of pre-configured blocks groups, avoiding to recreate it
on multiple pages.

![add-new-block-group-plus](data/images/add-new-block-group-plus.png)

This pre-configured list of page models and group of blocks can be configured in
many places.

As an example, [four page models], one for each of the page templates of the
module, and one group of blocks are available by default. The page models are
similar to the ones of Omeka Classic (Omeka 2) to simplify migration.

- Page models:
  - Home Page: A page template with blocks browse preview, html, heading, and
    asset.
  - Exhibit: A page template for an exhibit summary, with blocks page title,
    html, heading, and list of pages.
  - Exhibit page: A page template for an exhibit page, with blocks page title,
    html, heading and media.
  - Simple page: A page template for generic needs, with blocks page title and
    html.
- Blocks groups:
  - Resource with text: It appends a block Group with class "block-group-resource-text",
  a block "Heading" with level 3, a block Media, and a block Html.

The block "blockGroup" is not required and the group can be a simple list of
blocks, so you can configure the blocks of all a page type. If you use the block
Group, you may specify the data span for the number of the following blocks
nested inside it. If not set, all blocks until the next block Group will be
nested.

A page model or a group of blocks is just a pre-configured list of blocks of
settings, similar to the api representation of a site page, that are available
in https://example.org/api/site_pages. You can change settings once appended to
the page.

A page model or a blocks group can be defined via a simple click in the page
"Edit page". So the current page or the current list of blocks with all of their
settings will be stored as main setting, site settings or theme settings (if the
theme supports it).

Such page models and groups of blocks can be configured in any module, in the
file config/local.config.php of the installation, in the main settings, in the
site settings, in the file config/theme.ini of the current theme or in the theme
settings.

A configured element is a page model when the first level key "o:layout_data" or
"layout_data" is set with an array, even empty. If the key is not present, the
element is a blocks groups. Page models are not displayed in the list of blocks
groups in the "Edit page".

#### Example of config and settings

To set the list of page models or groups of blocks in a file (module.config.php,
local.config.php), follow the example in the file [module.config.php](https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/blob/master/config/page_models.config.php?ref_type=heads)
of this module.

To set the list of page models or groups of blocks in a field in a settings page
(main settings, site settings, theme settings), use the format "ini".

```ini
[asset_text]
o:label = "Asset with text"
o:caption = "Blocks group with a heading, an asset, a field for html and a line break with some styles"
o:block.1.o:layout = "blockGroup"
o:block.1.o:layout_data.class = "block-asset-text"
o:block.2.o:layout = "heading"
o:block.2.o:data.text = "Image"
o:block.2.o:data.level = 3
o:block.3.o:layout = "asset"
o:block.4.o:layout = "html"
o:block.4.o:data.html = "<p>This <strong>image</strong> represents…</p>"
o:block.5.o:layout= "lineBreak"
o:block.5.o:data.break_type = "opaque"
o:block.5.o:layout_data.background_color = "#404e61"
o:block.5.o:layout_data.min_height = "18px"
```

Set the name as section, a label as `o:label`, a description as `o:caption`, a
list of blocks with `o:block` and keys `o:layout`, `o:data` and `o:layout_data`.
If you want to create a page model, add a key `o:layout_data` with an array as
value. Key `o:data` does not exist for page model. All values are  optional,
except the layout name for each block (key `o:layout`).

For simplicity, you can skip the prefix `o:` for keys `o:label`, `o:caption`,
`o:block`, `o:layout`, `o:data`, and `o:layout_data`, so you can use `label`,
`caption`, `block`, `layout`, `data`, and `layout_data`, for example the
following config is the same than above:

```ini
[asset_text]
label = "Asset with text"
caption = "Blocks group with a heading, an asset, a field for html and a line break with some styles"
block.1.layout = "blockGroup"
block.1.layout_data.class = "block-asset-text"
block.2.layout = "heading"
block.2.data.text = "Image"
block.2.data.level = 3
block.3.layout = "asset"
block.4.layout = "html"
block.4.data.html = "<p>This <strong>image</strong> represents…</p>"
block.5.layout= "lineBreak"
block.5.data.break_type = "opaque"
block.5.layout_data.background_color = "#404e61"
block.5.layout_data.min_height = "18px"
```

#### Theme config and theme settings

The theme settings work the same than the main and site settings, but there
should be a form element to store them, so add this to your file `config/theme.ini`
first:

```ini
element_groups.pages = "Pages"

elements.page_models.name = "page_models"
elements.page_models.type = "Common\Form\Element\IniTextarea"
elements.page_models.options.element_group = "pages"
elements.page_models.options.label = "Page models and blocks groups"
elements.page_models.options.info = "Page models and blocks groups are configured as ini."
elements.page_models.options.documentation = "https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus#example-of-config-and-settings"
elements.page_models.attributes.id = "page_models"
elements.page_models.attributes.rows = "12"
```

And the user will input:

```ini
[asset_html]
label = "Asset and html"
block.1.layout = "asset"
block.2.layout = "html"
```

The theme may contain static page models too. For them, add them with the key
"page_models", because the use of section mark `[xxx]` is not possible, since
a section is already set with `[config]`:

```ini
page_models.my-page-model.label = "My page model"
```

#### Priorities

When two elements have the same name (root key), the more specific replaces the
more generic, so theme settings > theme config > site settings > main settings > config.

### New site page blocks and block templates

#### Asset (new templates)

Available templates:

- Bootstrap Hero
- Class - Url
- Left / Right
- Partners

These templates support the "class and url from caption" feature: if you need a
specific class or url for the theme and not only a page, you can prepend them to
each asset caption:

```
url = https://example.org/
class = xxx yyy
Next lines are the true caption.
```

Furthermore, the caption may be an html one.

#### Block

A simple block to display a template from the theme. It should be adapted by in
the theme. It may be used for a static html content, like a list of partners, or
a complex layout, since any Omeka feature is available in a view.

Two block templates are provided:

##### Arborescence / Tree view

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

For more complex hierarchy, it is recommended to use modules [Reference] or [Thesaurus].

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

### Browse preview (templates)

Some specific templates are available in Browse Preview:

- simple carousel ("browse-preview-carousel"): this is an upgrade of the plugin
  [Shortcode Carousel] for [Omeka Classic].
- gallery display with a quick viewer too ("browse-preview-gallery"). This one
  has a specific option to add to the query to display thumbnails as square or
  medium: `thumbnail_size=medium`. You can see an example on the site
  [Ontologie du christianisme médiéval en images], from French [Institut national de l’histoire de l’art].
  If you don't want to use the argument, use the other template "browse-preview-gallery-medium".
  The template "browse-preview-gallery-medium-item" allows to querying on media,
  but displaying items metadata and link to item.
- filter year: added a select on the top to filter the items by year.
- subjects: append the subjects to each resource.
- timeline list: display the list of resources by year and by date. Adapted from
  the plugin [Timeline Shortcode].

To use them, simply select the wanted template:

![browse-preview-carousel](data/images/browse-preview-carousel.png)

#### Buttons

This block displays buttons to share the current page in a privacy-compliant way.
Available buttons are Download, Email, Facebook, Pinterest and Twitter.

#### D3 Graph

The D3 Graph block uses the [D3 library] (version 7.9) to display an interactive
force-directed graph showing relations between resources: links between subjects
and objects, links between items and item sets, etc. Users can drag nodes to
rearrange the graph.

An example of use can be seen on the digital library of the [Fondation de la Maison de Salins].

##### Configuration

The block accepts a JSON configuration in the "Params" field:

```json
{
    "items": {
        "limit": 100
    },
    "item_sets": null,
    "relations": [
        "objects",
        "subjects",
        "item_sets"
    ],
    "config": {
        "height": 800,
        "forceCharge": -100,
        "forceLinkDistance": 100,
        "baseCirclePow": 0.6,
        "baseCircleMin": 5,
        "fontSizeTop": 35,
        "fontSizeMin": ".1px",
        "fontSizeMax": "16px"
    }
}
```

##### Parameters

| Parameter    | Type         | Description                                                      |
|--------------|--------------|------------------------------------------------------------------|
| `items`      | object/null  | Query parameters to filter items (e.g., `{"limit": 100}`).       |
|              |              | Set to `null` to exclude items.                                  |
| `item_sets`  | object/null  | Query parameters to filter item sets. Set to `null` to exclude.  |
| `relations`  | array/object | Types of relations to display (see below).                       |
| `config`     | object       | Visual configuration (see below).                                |

##### Relations Configuration

The `relations` parameter supports two formats:

**Simple list** (all properties):
```json
"relations": ["objects", "subjects", "item_sets"]
```

**With property filters** (specific properties only):
```json
"relations": {
    "objects": ["dcterms:relation", "dcterms:hasPart"],
    "subjects": ["dcterms:isPartOf"],
    "item_sets": null
}
```

| Relation Type | Description                                                        |
|---------------|--------------------------------------------------------------------|
| `objects`     | Resources linked as objects (current resource references them).    |
| `subjects`    | Resources linked as subjects (they reference current resource).    |
| `item_sets`   | Item set membership (for items only).                              |
| `items`       | Items belonging to item sets (for item sets only).                 |

When using property filters, set the value to `null` or `[]` to include all
properties for that relation type.

##### Visual Configuration Options

| Option              | Default | Description                                                 |
|---------------------|---------|-------------------------------------------------------------|
| `height`            | 800     | Height of the graph in pixels.                              |
| `forceCharge`       | -100    | Repulsion force between nodes (negative = push apart).      |
| `forceLinkDistance` | 100     | Default distance between linked nodes.                      |
| `baseCirclePow`     | 0.6     | Exponent for calculating node size from connection count.   |
| `baseCircleMin`     | 5       | Minimum radius of nodes in pixels.                          |
| `fontSizeTop`       | 35      | Connection threshold for showing large font.                |
| `fontSizeMin`       | ".1px"  | Font size for nodes below the threshold.                    |
| `fontSizeMax`       | "16px"  | Font size for nodes above threshold or special types.       |

##### Examples

Display items from a specific item set with their relations:
```json
{
    "items": {
        "item_set_id": 5,
        "limit": 50
    },
    "relations": ["objects", "subjects"]
}
```

Display item sets and their member items:
```json
{
    "item_sets": {
        "limit": 20
    },
    "items": {
        "limit": 200
    },
    "relations": ["item_sets"]
}
```

Display only `dcterms:relation` and `dcterms:isPartOf` links:
```json
{
    "items": {
        "limit": 100
    },
    "relations": {
        "objects": ["dcterms:relation", "dcterms:isPartOf"],
        "subjects": ["dcterms:hasPart"]
    }
}
```

##### Styling

The graph can be styled via CSS. Key classes:
- `.d3-graph`: Container element
- `.node`: All graph nodes
- `.node.item`, `.node.item-set`, `.node.value`: Node types
- `.link`: Lines connecting nodes
- `.cluster`: Node circles

Example CSS customization:
```css
.d3-graph .node.item circle { fill: #1f77b4; }
.d3-graph .node.item-set circle { fill: #ff7f0e; }
.d3-graph .link { stroke: #999; stroke-opacity: 0.6; }
```

#### External content

Similar to media or asset, but to display an external asset that is not a
resource neither an asset file, so currently not manageable inside Omeka. With
a block of html in a group of blocks, it may be used to display a html text with
a video on the home page.

A template is available:
- external-content-html: prepend html when it is in a group of blocks with html.

#### Heading

Display a html heading `<h1></h1>` to `<h6></h6>` in order to organize your
blocks.

Some templates are available:
- heading-details-start and heading-details-end: allow to wrap next blocks
  inside a html structure details/summary.
- heading-ling: allow to set a link as a title. Set the anchor directly in the
  field like `<a href="xxx">Title</a>`.
- skip: do not display anything. To be used with a group of block to get html.

#### HTML (js improvements and templates)

Two new options are added in main settings:

- Display the html field as a document, that is a lot easier when editing long
  articles. Furthermore, the field can be maximized.
- Use the default or advanced toolbar. The advanced toolbar is the CKEditor one
  and contains more possibilities to edit advanced text.

Furthermore, it is possible to add footnotes inside each html field.

For the templates:
- accordion: display an accordion, for example for a faq. The accordion parts
  are set with the html heading `h3`.
- dialog: display a html dialog box. The name of the dialog box should be set in
  the field "class", for example `dialog-info`. To open it, add a button or an
  anchor somewhere in the page with the class "button-dialog" and the attribute
  "data-dialog-name", that should be the same than the name of the dialog:
  `<button class="button-dialog" data-dialog-name="dialog-info">Open me!</button>`.
- glossary: display a glossary (see block Block above).
- page header: a default template with a specific class.
- skip: do not display anything. To be used with a group of block to get html.

#### Item Set showcase (deprecated)

This is similar to the block Media, but for item sets.
This block will be merged in block Showcase in a future version.

#### Links

Allow to display a list of links. In the text area, write each link as:
```
url = Title = Optional short description
/s/main/page/beta = Beta = Short description
```

#### List of Sites (improvement)

This block improves the core one in order to skip current site and translated
sites.

This feature may be integrated in the core.

#### Messages

Display the messages (notice, warning and errors), like in admin. This block may
be useful for modules like Contact Us, Contribute, Guest, Bulk export, etc. that
add some interactions with the visitor.

#### Mirror page

Allow to use a page as a block, so the same page can be use in multiple sites,
for example the page "About" or "Privacy". Of course, the page is a standard
page and can be more complex with multiple blocks. May be fun.
This is an equivalent for the [shortcode as a page] in [Omeka Classic] too.

#### Page Metadata

Allow to specify some metadata to the page for theme creators.

#### Page Title

A template is added for this core block:
- skip: do not display anything. To be used with a group of block to get html.

#### Redirect to URL

Allow to redirect the page to another page, inside or outside Omeka. It is
useful for hard coded links in the footer, to keep track of some clicks, to use
the page item/browse as a the home page, or some other use cases.

#### Search form

Include a specific search form in a specific page. The default query may be
adapted to the page via the theme.

A template is available:
- search-form-html: prepend html when it is in a group of blocks with html.

#### Search form and results

Create a full search page with a simple or complex form and the results on the
same page. All options should be managed via the theme. To replace item/browse,
item-set/browse and even media/browse, you may need to set the page as default
action for the search in default template `common/search-form`.

#### Showcase

Generic complete block to display any selected resources, site, page, asset or
url.

A template is available:
- showcase-html: prepend html when it is in a group of blocks with html.

#### Table of contents (improvement)

The table can be displayed from the root if wanted.

This feature may be integrated in the core.

#### Tree structure

Display a tree structure from selected resources. The structure is generally
built from the property Dublin Core : Has Part.

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

### New Resource page blocks

- Resource Type: Display the resource type (item set, item or media).
- Thumbnail: Display a large thumbnail of the resource
- Title: Display the title of the resource.

#### Block

A block that does nothing, but that may be useful for theme developer.

#### Buttons

This block displays buttons to share the current page in a privacy-compliant way.
Available buttons are Download, Email, Facebook, Pinterest and Twitter. The
config should be set in site settings.

#### Description

Display the description of the resource.

#### Div and Div tools (start and end)

Allow to wrap a list of block with a html element `<div>`. It is useful to use a
theme natively. They are the same, but with a different class. You can use the
block Html Section too.

#### Media Part Of Item

Add a link to the item from the media.

#### Messages

Display the messages (notice, warning and errors), like in admin. This block may
be useful for modules like Contact Us, Contribute, Guest, Bulk export, etc. that
add some interactions with the visitor.

#### No Item

Add a text to indicate that the item set has no item.

#### No Media

Add a text to indicate that the item has no media.

#### Previous/Next

Allow to display buttons previous resource and next resource in the list.

Warning: to define what is the previous or next resources is not so simple,
because it may be the order in a item set, or order in the last results of a
research.

For order in item set when an item is in multiple item set, an option in main
settings allows to define which property define it. Another option allows to
define the default order.

When a search is done, the list is built from the user last browse query, else
from natural order or the item set one.

The default order without previous browse or search can be set in site settings
for items and item sets. For media, the order is defined in item.

This block requires the module [Easy Admin].

It can be used as a theme helper too (see below).

#### Section (start and end)

Allow to wrap a list of block with a html element `<section>`. It is useful to use
a theme natively.

#### See Also

Display a list of related resources based on the current resource's metadata.
Related resources are found via a fulltext search using values from configured
properties (e.g., dcterms:subject, dcterms:type), or from a predefined query.

The following options can be configured in site settings:
- Heading: Text displayed above related resources (default: "See also")
- Limit: Maximum number of related resources to display (default: 4)
- Query: An api query to predefine the pool of resources. When set, properties
  are not used.
- Properties: Which properties to use for finding related resources. Used only
  when query is empty.
- All sites: When checked, related resources are searched across all sites, not
  just the current one.

The block uses the same classes as the core browse-preview block for consistent
styling.

#### Similar Content

Display a list of similar resources based on the current resource's metadata.
This block is a duplicate of "See Also" with independent settings, allowing two
different configurations for different use cases (e.g., "See Also" for related
items within the same collection, and "Similar Content" for recommendations
based on different criteria).

The following options can be configured in site settings:
- Heading: Text displayed above similar resources (default: "Similar content")
- Limit: Maximum number of similar resources to display (default: 4)
- Query: An api query to predefine the pool of resources. When set, properties
  are not used.
- Properties: Which properties to use for finding similar resources. Used only
  when query is empty.
- All sites: When checked, similar resources are searched across all sites, not
  just the current one.

### Theme view helpers

#### Block Metadata

This block provides the same information than the view helper `pageMetadata()`
(see below), but from a block. It is usefull with the simple block to extract
params, or to get some informations about the page from anywhere in the theme.

```php
// A simple check is done to make the theme more generic.
$blockMetadata = $plugins->has('blockMetadata') ? $plugins->get('blockMetadata') : null;
if ($blockMetadata) {
    $data = $blockMetadata('params_key_value');
}
```

#### Caption Class And Url And Label

The view helper `CaptionClassAndUrl()` allows to extract a class, a url and a
label from the start of a string. The optional class, url and label may be set
at start of a caption like:

```
url = https://example.org/
class = xxx yyy
label = Any zzz
Next lines are the true caption.
```

The initial caption may be an html one.

The output is a simple array containing caption, class, url, and label and flags
for local url (relative url), local media file, and html.
Get result like:

```php
[$caption, $class, $url, $label, $isLocalUrl, $isMediaFile, $isHtml] = $this->captionClassAndUrl($string);
```

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
if ($pagesMetadata) {
    $data = $pagesMetadata('exhibit_page');
}
```

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

#### Module page template and module block templates

A module can add page and block templates to theme: just add them in the file
config/module.config.ini of the module under keys `page_templates` and `block_templates`,
for example:

```php
    'block_templates' => [
        'asset' => [
            'asset-bootstrap-hero' => 'Block Plus: Bootstrap Hero', // @translate
        ],
    ],
```

See more info on page templates and block templates in [user doc] and [dev doc].


Usage until version 3.4.21 for Omeka S until v4.0
-------------------------------------------------

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

#### Block

See above.

### Browse preview (improvements and templates)

The block Browse preview has new fields to display sort headings and pagination,
so it's now possible to have a specific list of items, like the main browse view.

For the specific templates, see above.

#### D3 Graph

See above.

#### Division

Allow to wrap a block or multiple block with a `div`. in particular to create
columns. Divisions can be nested. The css should be prepared in the theme to
managed them. By default, only a simple `aside` column of 30% is available with
class `column align-right` (or left).

#### External content

Allow to display an external resource. The block contains an html textarea too.
See above.

#### HTML (templates)

See above.

#### Item Set showcase

See above.

#### Mirror page

See above.

### Page date

Display the date of the creation and/or modification of the current page.
See Omeka issue [#1706].

#### Redirect to URL

See above.

#### Resource with html

Simplify the display of a media on the left or the right (see [user guide]). It
is the same block that existed in [Omeka Classic] [Exhibit `file-text`]. It was
migrated to a block Group with blocks Media and Html.

#### Search form

See above.

#### Search form and results

See above.

#### Separator

Allow to set a div with a specific class between two blocks. May be useful to
fix some css issues, or to increase space between some blocks.
The content of the block is:
```html
<div class="break separator"></div>
```

Replaced by block "Line Break".

#### Showcase

See above.

#### Table of contents (improvement)

See above.

#### Twitter

See above.

### Theme view helpers

See above.


Migration of themes in version v3.4.22/23
-----------------------------------------

**Warning**: when a page is saved, all remaining settings that are not managed
by the form are removed. So the best way to see issues is to load all pages
before migration and to load all pages after migration _**AND**_ resaving each
page.

See more info on page templates and block templates in [user doc] and [dev doc].

### Core blocks are no more overridden

The following blocks are no more overridden:

- Asset (but additional templates were added to support asset class and url via
  caption, and html caption)
- Browse preview
- Item showcase (renamed media)
- Item with metadata
- Html
- List of pages
- Page date
- Page title

The following blocks are no more overridden, but contains a fix/feature and
will be removed soon when integrated upstream:
- List of sites
- Table of contents

### Move specific block-layout templates to block-template templates

The new templating mechanism of Omeka S v4.1 uses a new directory in theme to
allow to use block template. You should move all templates from `view/common/block-layout`
to `view/common/block-template` that were a template managed by this module, but
not the default templates named like the original ones.

For example, if you used the block "Block" with the default template customized
in your theme, let it as "view/common/block-layout/block.phtml". But if you used
the same block with a module template, move it to "view/common/block-template/block-glossary.phtml".

Then, you should add the specific templates in the file config/theme.ini of the
theme.

### New names of deprecated templates

Some templates are deprecated and were renamed with a "-plus" and moved in
directory `view/common/block-template`. The migration moved the option to use
the new template name.

- "asset" => "asset-deprecated-plus"
- "browse-preview" => "browse-preview-deprecated" or "search-results-browse-preview-deprecated"
- "item-with-metadata" => "item-with-metadata-deprecated"
- "list-of-pages" => "list-of-pages-deprecated"
- "list-of-sites" => "list-of-sites-deprecated"
- "item-showcase" => "media-item-showcase-deprecated",
- "file-item-showcase" => "media-item-showcase-deprecated"
- "table-of-contents" => "table-of-contents-deprecated"

This template was renamed, but not deprecated:
- "page-date-time" => "page-date-time-plus"

You should add the specific templates in the file config/theme.ini of the theme
if you really need them. Else, it is recommended to use the default template.

You should check the styles of all of them.

### Check styles for blocks heading and html

The option `heading` of all blocks were removed. A new block "Heading" was
added and the migration prepended it automatically. But the `<div>` is no more
inside the `<div>` of the previous block and the heading level is always
converted to 2, whatever the level was.

**Warning**: the block "Browse preview" still uses the setting "heading".

It is the same for the option `html` that was added in some blocks and moved to
a prepended standard block "html".

The core migration used the option `alignment` too in some blocks, that may have
created some style issues too.

### Check dynamic classes "$divclass", "$class", and "$className"

These classes were added to a main div of the block. They are now managed by the
core, that separated styling. These values are now managed in a upper div and
the migration moved them to the new layout data.

You should remove them from the phtml files of the themes and check if styles is
right in all pages.

### Block Asset

For block Asset, the keys "class" and "url" of assets were moved to the top of
the caption and automatically extracted in templates.

```
url = https://example.org/
class = xxx yyy
Next lines are the true caption.
```

You should check the theme manually if you use this feature and get class and
url like other asset templates:

```php
[$caption, $class, $url, $label, $isLocalUrl, $isMediaFile, $isHtmlCaption] = $this->captionClassAndUrl($attachment['caption']);
```

### Block Browse preview

The block Browse Preview is no more managed by this module. Support of options
"html", "sort_headings" and "pagination" were removed. Check your themes if you
used it, or use block Search Results.

Normally, the migration converted this block into a block Search Results when
the specific features were used, else converted it in a block template for block
Browse Preview.

Furthermore, the variable `$site` is no more available in block templates for
Browse Preview, so you should add `$site = $block->page()->site();` or `$site = $this->currentSite()`
if you need it.

You should fix the theme manually.

### Block Division

The block Division was removed. When the divisions were flat, they were
converted into a group of blocks, a new feature of Omeka S v4.1.

When the divisions were nested, the migration was not done, so you should do it
manually with page template "grid" and/or blocks groups.

Furthermore, the option "tag", that can be "div" or "aside", is no more managed.

### Block External Content, appended with a block Html in a group of blocks

The block does not manage html any more, so it display an external resource. The
migration converts it to a group of blocks External Content and Html.
Furthermore, the option Caption Position is now a class of the block. So you may
check styles.

### Block Html

The variable `$html` is no more available in templates for block Html, so you
should get it via `$block->dataValue('html', '')`. A list of matched files was
added during migration and is available in logs.

You should fix the theme manually.

### Block Item Showcase (renamed Media)

The block Item Showcase was renamed Media in Omeka S v4.1. The option "linkType"
was renamed "link".

You should fix the theme manually.

### Block Resource Text, replaced by a group of blocks Media and Html

The block is now a group of blocks Media and Html, so structure and classes were
updated, so you may check styles.

### Block Separator, replaced by block Line Break

The block class is now in the block template, so you may check styles.

### Module upgrade

For modules that used the form select element "template", you should do the same
migration and use the same mechanism of block template. The select element will
be removed in a future version.


TODO
----

- [ ] Make page metadata available via resource templates, as any resource (or via a new module).
- [ ] Merge more similar blocks into a main block (with automatic upgrade).
- [x] Integrate Shortcodes (module [Shortcode])
- [x] Move Breadcrumbs to module [Menu] (v3.4.44).
- [x] Integrate attachments for block Showcase
- [x] Integrate sidebar forms for block Showcase
- [ ] Auto-create asset when image is uploaded in a Html field.
- [ ] Update site of mirror page to get good url for $resource->siteUrl().
- [x] Merge PageDate with upstream PageDateTime.
- [ ] Use new events "sort-config" and "view.sort-selector" to order item in item sets.
- [x] Allow themes to create page models and blocks groups.
- [ ] Add a button to create a page model from an existing page. See module Internationalisation.
- [x] Allow to create a group of block without block Group.
- [ ] Allow to create page models filtered by site (add setting o:site and filter).


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
The original template was fixed by KevinMwangi and alii, then updated for newer
version of components (modernizr, smartresize, imagesloaded), then upgraded to
native javascript.

### Timeline list

Adapted from [Timeline Shortcode].


Copyright
---------

* Copyright Daniel Berthereau, 2018-2025 (see [Daniel-KM] on GitLab)
* Copyright Codrops, 2013 ([image gallery], see vendor/ for more infos)
* Copyright Andy Kirk, 2014-2021 (See https://github.com/andykirk)
* Copyright Jan Sorgalla, 2014 (See http://sorgalla.com/jcarousel)


[Block Plus]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus
[Omeka S]: https://omeka.org/s
[installing a module]: https://omeka.org/s/docs/user-manual/modules/#installing-modules
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[shortcode as a page]: https://github.com/omeka/plugin-SimplePages/pull/24
[user doc]: https://omeka.org/s/docs/user-manual/sites/site_pages/#edit-a-page
[dev doc]: https://omeka.org/s/docs/developer/themes/theme_templates
[four page models]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/blob/master/config/page_models.config.php?ref_type=heads
[module.config.php]: https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/blob/master/config/page_models.config.php?ref_type=heads
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
[Timeline Shortcode]: https://github.com/ebellempire/TimelineShortcode
[Menu]: https://gitlab.com/Daniel-KM/Omeka-S-module-Menu
[Reference]: https://gitlab.com/Daniel-KM/Omeka-S-module-Reference
[Thesaurus]: https://gitlab.com/Daniel-KM/Omeka-S-module-Thesaurus
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
