# Function overview

## Basic formatting

The MarkupParser class is utilised to convert a simple, markdown-like syntax for basic text formatting and for creating
html or xml syntax:

- Abbreviations will get "abbr" tags. This requires the configuration of a dictionary with known abbreveations.
- Sidebars left / right / middle, which have to be enclosed with single lines (`<---`, `<--->` or `--->`)
- Codeblocks (same syntax as markdown)
- Footnotes:
    - Reference anywhere in the text: `[^no]`
    - Definition of the footnote starting in a new line: `[^no]: Description`
- Headlines starting with one or more hash signs
- Horizontal rules: single line filled with three or more of these chars: `-`, `*`, `_` 
- Block quotes (starting one or more lines with `> `)
- Bullet lists: lines starting with `-`
- Numbered lists: lines starting with a number followed by a dot

Inline formatting:
- `**bold**`
- `*italic*`
- `_underlined_`
- `~strike-through~`
- `verbatim` (code)
- `$variable$`
- `^superscript^`
- `°subscript°`
- `|small caps|`

## NanoCM replacements

NanoCM's HtmlConverter class does some additional replacements. All of these should be replaced
by plugins in the future.

- Youtube-Videos are replaced by a (cached) preview image: `[youtube:www.youtube.com/watch?v=ABC]`
- Photo albums: `[album:folder id]`
- Single imaged: `[image:id:format id]`
- Download links: `[download:file id]`
- Twitter support has recently been removed from NanoCM (Twitter now is X and dead)

## Additional content plugins

More content formatting functions are provided by plugins. All plugins share the same syntax. Placeholders have
to start with a single line

`[pl:plugin name]`

and have to be closed with a single line

`[/pl:plugin name]`

Within these lines arbitrary options are allowed. These options are formatted key value pairs, separated by a semicolon.
Every option has to be in a single line. Supported options depend on the actual plugin.

Options are passed via the local variable `$params` to the rendered user template. (This could be implemented better in
the future.) Plugin templates should be placed in the sub directory `plugin` within the template directory.

### slideshow

Inserts a slide show containing all images from one specified media folder. The actual presentation depends on the provided
template. A slide show can be played automatically or may present user controls for manual navigation.

Supported options:
- `folderid`: (required) The numeric id of the folder to be displayed
- `format`: (required) The name(!) of the thumbnail image format, defined in the media manager
