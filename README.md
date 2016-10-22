# tcl-status
WordPress plugins that shows information about Toolset plugin versions, branch names
(if the plugins are in a git repository ) and the loaded Toolset Common Library 
instance in the admin bar.

![screenshot](./screenshot.png)

The first item shows the information about the Toolset Comon library: 

>`tcl: ` *{from what plugin is the library loaded}* `(` *{$toolset_common_version}* `@` *{branch name}* `)`

Remaining items show active Toolset plugins, their version and branch. 
Following plugins are supported:

- Toolset Types
- Toolset Views
- Toolset CRED
- Toolset Layouts
- Toolset Access

## Installing
 - Clone or download the plugin into the `wp-content/plugins` directory in your site
   - `git clone https://github.com/zaantar/tcl-status.git`
 - Activate the plugin.
   - `wp plugin activate tcl-status`