# Infrangible CMS Filter

Extends the template syntax of CMS pages, CMS blocks and widgets on the storefront: conditions inside loops, short
placeholders for variables and translations that work inside the parameters of widgets, and variables whose value is
processed as a template itself. Used by [Infrangible Retail Store](https://www.infrangible.dev/modules/retail-store)
for its store lists and detail pages.

## Directives

| Directive                                  | Result                                                                               |
|--------------------------------------------|--------------------------------------------------------------------------------------|
| `{{fif <variable>}}...{{fielse}}...{{/fif}}` | inside `{{for}}`: a condition on a value of the current entry, `{{fielse}}` optional |
| `#var <variable>#`                         | the value of the variable, like `{{var}}` without escaping; filters like `#var name\|escape#` |
| `#trans "<text>"#`                         | the text, translated into the language of the store view                             |
| `{{var <variable>\|filter}}`               | the value of the variable, processed as a template again                             |

Example of a list of stores with the variable `retail_stores`, a list of arrays or data objects:

```html
{{for store in retail_stores}}
  <h3>{{var store.name}}</h3>
  {{fif store.has_opening_hours}}
    <span class="label">#trans "Opening Hours"#:</span> {{var store.opening_hours}}
  {{fielse}}
    #trans "By appointment"#
  {{/fif}}
{{/for}}
{{widget type="Infrangible\GoogleMaps\Block\Widget\GoogleMaps\JsonMarker" marker_json="#var marker_json#"}}
```

- `{{fif}}` is needed because Magento processes `{{if}}` before `{{for}}`, with the variables outside the loop. The
  condition is not met for empty values: `null`, `false`, an empty text or list and numeric zeros like `0` and `"0"`.
- Loops accept arrays and other lists (collections), and the variable `loop.index` (starting at 0). Like in Magento,
  the content of a loop only replaces variables; other directives in it are not processed, except inside `{{fif}}`.
- `#var#` and `#trans#` are replaced before all other directives, so they can be used where curly braces are not
  possible, for example in the parameters of a widget. `#var` must be followed by a space, so links to anchors like
  `#variants` stay as they are.

The directives are available in the templates filtered on the storefront (CMS pages and blocks, widgets, e-mails sent
from the storefront).

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.2 or newer

## Installation

```bash
composer require infrangible/m2-cms-filter
bin/magento module:enable Infrangible_CmsFilter
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile`.

## License

Infrangible CMS Filter is licensed under the MIT License - see the LICENSE file for details.
