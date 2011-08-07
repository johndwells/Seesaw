Seesaw Add-On (0.5.0)
======================================================================

Seesaw is an ExpressionEngine 1.6x extension that allows you to configure
the Edit Channel Entries page to show/hide columns on a channel-by-channel basis.

The premise is simple:

For the default view, when all available channels are displayed, Seesaw
allows you to show/hide the "core" columns (ID through Status).

If you then filter the list with the search form so as to limit results
to only one channel, Seesaw allows you to show/hide any custom fields
assigned to that channel.

This works great with the Edit Menu and Edit Tab AJAX, and early tests
indicate it plays nice with other add-ons that modify the
Edit table (Weegee, Cloner, Live Look, etc).

Made by [John D Wells](http://johndwells.com).




Similar Plugins:
----------------------------------------------------------------------

This has been approached before, I'm just taking a slightly different
angle. But it's worth giving a nod to those who came before me:

- [NB Show Custom Field Data](http://nicolasbottari.com/index.php/expressionengine/nb_show_custom_field_data/)
- [Edit Custom Field By Label](http://expressionengine.com/forums/viewthread/87651/#493724)
- [Simplify Edit Table](http://expressionengine.com/downloads/details/simplify_edit_table/)



Installation:
----------------------------------------------------------------------

1. Copy lang.seesaw.php into /system/language/english/
2. Copy ext.seesaw.php into /system/extensions/
3. Enable the extension via the Extensions Manager
   (Admin > Utilities > Extensions Manager)
4. Visit the Seesaw settings page to configure the various Channel views



Upgrading:
----------------------------------------------------------------------

Simply overwrite extension & language files.



Type/Format Configuration:
----------------------------------------------------------------------

Text (char limit):
Select this option to limit the field output to a certain number of characters.
Enter that limit count into the "format" field, e.g. "30" (without quotes).
If no value is given, the entire field will be output.

Timestamp:
Select this option to convert a timestamp into a human-readable format.
Enter the format string into the "format" field, e.g. "%y %m %d" (w/out quotes).

MX UniEditor Img (NEW):
Supports Max Lazar's MX UniEditor Img extension, showing thumbnail.
No format value required.

Custom HTML (NEW):
Allows to freely format field as desired using HTML. Use "{value"}
(without the quotes) to access field value, e.g. "<em>{value}</em>"



Requirements:
----------------------------------------------------------------------

Seesaw was built on ExpressionEngine 1.6.8, though it uses hooks
available since 1.4.0. If you manage to install it successfully
below 1.6.8, please let me know.



Changelog:
----------------------------------------------------------------------
0.5.0
- Bug fix for when member group only has access to a single channel


0.4.9
- Support for PHP4
- Rebuilt column replacement to be less destructive. Initially to play nicer with
  Edit Table Plus, but should benefit others too


0.4.8
- Gypsy support


0.4.7
- Fix compatibility with Webee Quick
- Avoid notices thrown when Brandon Kelly's Gypsy extension is in use
- Fix call to LG addons (hopefully)
- Fix php4 construct method name


0.4.6
- Complete support for LG Addon Updater
- Add support for custom_html
- Add support for MX UniEditor Img
- Improve input scrubbing & prep for DB insertion
- Bug Fix: [See forum post](http://expressionengine.com/forums/viewthread/139168/#686144)



Possible Upcoming Features:
----------------------------------------------------------------------
- Add Gypsy support
- Add an nGen File Field type (show as thumbnail)
- Add a URL format field
- Add FF Matrix support (to show row count)
- Allow for all Channel views to inherit Defaults
- Allow to show other "core" fields, e.g. expiration_date


