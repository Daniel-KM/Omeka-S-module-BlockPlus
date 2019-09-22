Block Plus (module for Omeka S)
===============================

[Block Plus ] is a module for [Omeka S] that adds some new blocks for the static
pages and improves some of the existing ones.


Installation
------------

Uncompress files and rename module folder `BlockPlus`. Then install it like any
other Omeka module and follow the config instructions.

See general end user documentation for [Installing a module].


Usage
-----

Select them in the view "Page edit". You may theme them too: copy the block
templates that are in `view/common/block-layout/` in the same place of your
theme.

### Assets

Display a list of assets with optional urls and labels. It’s useful to build a
block of partners, for example. The assets are not required to be filled, so it
allow to display any list of contents.

### Simple block

A simple block allow to display a template from the theme. It may be used for a
static html content, like a list of partners, or a complex layout, since any
Omeka feature is available in a view.

### Simple page

Allow to use a page as a block, so the same page can be use in multiple sites,
for example the page "About" or "Privacy". Of course, the page is a standard
page and can be more complex with multiple blocks. May be fun.
This is an equivalent for the [shortcode as a page] in [Omeka Classic] too.

### Resource with html

Simplify the display of a media on the left or the right (see [user guide]). It
is the same block that existed in [Omeka Classic] [Exhibit `file-text`].

### Embedded asset with html

Similar to media with html, but to display an external asset that is not a
resource neither an asset file, so currently not manageable inside Omeka. It may
be used to display a html text with a video on the home page.

### Search form

Include a specific search form in a specific page. The default query may be
adapted to the page via the theme.

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
main browse view.


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


Copyright
---------

* Copyright Daniel Berthereau, 2018-2019 (see [Daniel-KM] on GitHub)


[Block Plus]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus
[Omeka S]: https://omeka.org/s
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[shortcode as a page]: https://github.com/omeka/plugin-SimplePages/pull/24
[Omeka Classic]: https://omeka.org/classic
[Exhibit `file-text`]: https://omeka.org/classic/docs/Plugins/ExhibitBuilder
[user guide]: https://omeka.org/s/docs/user-manual/sites/site_pages/#media
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://http://opensource.org/licenses/MIT
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
