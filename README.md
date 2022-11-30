# Register Block Styles

This is a plugin for WordPress when using the block editor and/or full-site editing.  It provides a way of registering block styles and enqueueing block style CSS files via convention and configuration instead of having to write repetitive code spread across PHP and JavaScript.

In order to reduce redundancy for loading block style CSS files from your theme directory, you must use this directory convention for the block style CSS files:
```
├── build
│   ├── block-styles
│   │   ├── [style-name].css          # styles used by multiple blocks (shared)
│   │   ├── [style-name].css
│   │   ├── ...
│   │   ├── [block-namespace]         # block namespace eg "core"
│   │   │   ├── [block-name]          # block name eg "group"
│   │   │   │   ├── [style-name].css  # block style CSS
│   │   │   │   ├── [style-name].css
│   │   │   │   └── ...
│   │   │   ├── [block-name]
│   │   │   ├── ...
│   │   ├── [block-namespace]
│   │   ├── ...
```

Styles used by multiple blocks are located at the root of the block-styles directory. Styles specific to one block are located in the subdirectory `[block-namespace]/[block-name]`.

You must also add the configuration file `block-styles.json` to the root of your theme, which is structured like this:

```json
{
  "stylesDir": "dist", // default is "build/block-styles", include only if needed.
  "blocks": {
    "block-namespace/block-name": {
      "unregister": [
        "style-to-unregister",
        "other-style-to-unregister",
      ],
      "register": [
        {
          "name": "style-to-register",
          "label": "Name of Block Style",
          "isDefault": true // default is false, include only if needed.
        }
      ],
      "registerShared": [
        {
          "name": "shared-style-to-register",
          "label": "Name of Block Style"
        }
      ]
    },
    // ... other blocks ...
  }
}

```

You may change `build/block-styles` to some other directory in your theme by setting `stylesDir` in the config file.

For each block entry:
- `unregister` is an array of block style handles (string) to unregister.  Styles for all blocks are unregistered before any new styles are registered.
- `register` is an array of block style configuration objects. This configuration is used to determine which CSS files to enqueue, and is passed to `wp.blocks.registerBlockStyle` (the plugin will amend it with the appropriate `style` handle for the matching CSS file). For each block style, the minimum attributes required are `name` and `label`.  `name` should match the CSS file name (as well as the style `.is-style-[name]`), and `label` is the front-facing style name in the block editor.
- `registerShared` is an array of block style objects, but unlike those blocks in `register`, these styles are located in the top-level namespace so that they can be registered to multiple blocks.

For every block in `blocks`, the plugin will:
- (PHP) enqueue block style CSS files.  In the future it could use `wp_enqueue_style()` if [this bug](https://core.trac.wordpress.org/ticket/55184) is fixed.
- (JS) unregister every style in a block's `unregister` array.
- (JS) register every style in a block's `register` and `registerShared` arrays.

## How to use

1. Download the latest release.
2. Install the plugin (drop it into the `wp-content/plugins` directory)
3. Add a config file named `block-styles.json` at the root of your theme.
4. Activate the plugin.

## Issues

This is pre-release software and might not work as expected.  Use at your own risk.  However, if you encounter a problem, feel free to open an issue and the maintainer will probably try to fix it.
