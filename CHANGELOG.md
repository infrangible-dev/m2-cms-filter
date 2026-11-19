# Changelog

## 2.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Older versions of Magento and PHP 7.1 to 8.1 are no longer
  supported; use version 1.x for these.
- Requires `infrangible/m2-foundation` 3.0 and `magento/module-email`; the module no longer uses `fewedev/base`, which
  it used before without requiring it.
- The loop `{{for}}` is replaced by a preference for Magento's `ForDirective` instead of an around plugin that never
  called Magento's method (`Infrangible\CmsFilter\Model\Template\Filter\ForDirective`, the plugin class is removed).
  `ForIfDirective` implements the directive interface itself instead of extending Magento's `IfDirective`.
- All classes have native types, and the constructors use constructor property promotion.
- `{{fif}}` treats numeric zeros (`0`, `"0"`) as empty, so the `{{fielse}}` part is shown.
- `#var` must be followed by whitespace and `#trans` by whitespace and a text in double quotes; neither may contain a
  hash character.

### Fixed

- Curly braces in the content of a loop are kept; before, all braces of the content (and of loops that could not be
  processed) were removed.
- `{{fif}}` with the value `0` showed the content of the condition instead of the `{{fielse}}` part, because PHP 8
  compares `0` and an empty string as different.
- The content of a loop without placeholders is shown for every entry; before, the loop returned nothing.
- Loops over lists that are not arrays (collections, which the variable resolver of older e-mail templates returns) no
  longer fail with a type error.
- Links to anchors starting with "var" (for example `href="#variants"`) are no longer taken for `#var#`; before, the
  content up to the next hash character was replaced.
- Arrays as values of placeholders in loops are replaced by an empty text instead of "Array" with a warning.
- The variables of a template filter are kept per filter object until the filter is destroyed; before, they were kept
  for the whole request by object hash, which a new filter can reuse.

### Added

- Unit tests.
- README with the syntax.

### Changed

- The module is loaded after the modules it uses.
- `{{var <variable>|filter}}` also works with further modifiers, for example `{{var content|filter|nl2br}}`.
- Checks with PHPStan level 9, the Magento coding standard and unit tests on every push.
