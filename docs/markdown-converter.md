# Markdown Converter

`MarkdownConverter` is a tiny built-in converter for lightweight package documentation or asset-adjacent text. It is not intended to replace a full CommonMark implementation.

## Convert Markdown

```php
<?php

use CommonPHP\Assets\MarkdownConverter;

$html = (new MarkdownConverter())->convert('# Assets');
```

`convert()` delegates to `toHtml()`.

## Supported Syntax

The converter supports:

- headings from `#` through `######`;
- paragraphs;
- unordered lists with `-` or `*`;
- fenced code blocks;
- `**strong**`;
- `*emphasis*`;
- HTML escaping.

Use a dedicated Markdown package if you need tables, nested lists, footnotes, blockquotes, custom renderers, or full CommonMark compatibility.
