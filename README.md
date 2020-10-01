# Component

Adding JS components to your Drupal site just got a whole lot easier. Just
combine your JS components (any type) with an `info.yml` and put it in the
codebase. Now, your component will be available in Drupal as a block -
automatically!

You can also add a configuration form to your component so site builders can
modify the component. This component "looks" like any other block, so it can be
used just like a core block.

JS devs don't need to know PHP or Drupal in order to integrate their components
into the CMS. They just need to setup the `info.yml` file properly.
## Info.yml
The `info.yml` file provides the JS developer with a ton of basic configuration
options. By modifying this file, you can provide static or dynamic parameters,
include additional libraries, and even adjust the cache configuration. See the
code comments on `example_tabs.info.yml` for details.

    name: Widget
    machine_name: widget
    type: component
    core_version_requirement:  ^8 || ^9
    js:
      widget.js: {}
    css:
	  widget.css: {}
Thats it! Put that info.yml file into a directory with the widget.js and
widget.css, and you now have a component.

## How it works
We rely on the auto-discovery mechanism built into Drupal to find the `info.yml`
file. This is what is able to find the modules, themes, and profiles in the
codebase. We simply add a new "type" to the mix - a **component**

A component is a block plugin called `ComponentBlock`. That means it's just an
extension of the block entity in Drupal. So, we can interact with components
just like we do for normal blocks!

The `info.yml` file tells Drupal what this component is called, where it's
assets are located, and how the block can be configured. The module creates a
library definition for each component, loads any other library dependencies, and
renders the default html to the page.

When the page loads, it has the html it needs (including custom elements) and
then the JS is run in the browser like normal.
## Contributions
- Inspired by the PDB module (mrjmd/decoupled_blocks)
- Kevin Funk

## Todo


### Backlog
- create some more examples - backbone, other core libraries
- create component_reference module
- create component_browser module?
- create component_builder module?
