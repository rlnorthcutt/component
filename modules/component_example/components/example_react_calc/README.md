## Example React Calculator
This is an example of a calculator being provided as a React component. In this
case we are doing a few interesting things:
- react.min.js and react-dom.min.js are being loaded from a CDN directly
- we are using the in-browser Babel transformer (from a CDN)
- we are adding the babel attribute to the JS file list

By relying on external libraries, it is possible to build self-contained JS
components in most frameworks and styles, without extending the Component
module.

### Best practice
It is ideal to precompile your scripts (as normal) before adding to the
codebase. This example uses uncompiled code with the in browser trasnformer so
it is easier to use without needing to precompile.

If you plan to have multiple components using a library like React (as is
normal), then it would be best to create a Drupal library listing and list that
as a dependency in the info.yml file. This not only allows you to more easily
set and control the versions used for your component library, but it also means
that the JS Aggregation tools in Drupal can more easily manage the files without
duplicates.

You can create a library with a libraries.info.yml file, or as a "component"
that does very little (called "react_bundle"). Then the library could be
included like this
`dependencies:
  - component/react_bundle`

Additionally, it is possible to use webpack or other methods for packaging, but
keep in mind that we want to reuse shared libraries when possible.

### Source
https://codepen.io/jlmortola/pen/VPYaEv
