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
partials that are in `view/common/block-layout/` in the same place of your
theme.

### Assets

Display a list of assets with optional urls and labels. It's useful to build a
block of partners, for example.

### Media with html

Simplify the display of a media on the left or the right (see [user guide]). It
is the same block that existed in Omeka Classic Exhibit `file-text`.

### Embedded asset with html

Similar to media with html, but to display an external asset that is not a
resource neither an asset file, so currently not manageable inside Omeka. It may
be used to display a html text with a video on the home page.

### Improvements for Browse Preview and Item Showcase

Allow to use a specific partial for some blocks, so it's possible to display
these blocks differently in the same page or on different pages.


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


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)


Copyright
---------

* Copyright Daniel Berthereau, 2018-2019


[Block Plus]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus
[Omeka S]: https://omeka.org/s
[Installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[Bootstrap]: https://getbootstrap.com
[user guide]: https://omeka.org/s/docs/user-manual/sites/site_pages/#media
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-BlockPlus/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[MIT]: http://http://opensource.org/licenses/MIT
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
