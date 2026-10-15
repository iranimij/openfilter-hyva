# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [1.1.0] - unreleased

Everything new is opt-in: with the 1.0 settings, the markup and behaviour stay as they were.

### Added
- Dropdown display type (a native select in a GET form, also without JavaScript), category tree levels, price ranges as options.
- Tooltips and the "Products have all picked values." hint at the top of the open group (visible, no hover needed).
- Desktop apply mode: picks are staged and a prefetched "Show N products" bar applies them.
- Horizontal layout: filter groups as popovers above the products (one open at a time, Escape and outside click close it); phones keep the drawer.
- Sticky sidebar (CSS only).
- Meta description, and the H1 when an H1 template is set, follow every AJAX swap.
- The 1.1 behaviour is a separate script printed only on store views that use a mode; the default page keeps the 6 KB budget.

## [1.0.0] - 2026-10-16

No changes since 1.0.0-rc1.

## [1.0.0-rc1] - 2026-10-15

First release: OpenFilter's storefront for Hyvä 1.4 and 1.5 (requires `iranimij/openfilter` and `hyva-themes/magento2-theme-module` ^1.3).

### Added
- Filter templates on category and search result pages: lists with "Show more" and a search box, swatches (colour, image, text), sliders on a GET form, rating, Yes/No switches, category drill-down; filter groups as native disclosures; chips, Clear all and the product count.
- Hyvä's product cards preselect the picked colour (or other swatch) by option id on filtered pages, as with Magento's own filter links, and keep their other options available.
- One Alpine component for same-URL AJAX: fetches the URL a normal page load would (served by the page cache), swaps the filters, chips and results, keeps history and scroll, syncs title, robots, canonical and prev/next, announces the product count, follows redirects; registered with Hyvä's CSP helper.
- Mobile drawer (native `<dialog>`) with staged changes and a prefetched "Show N products"; filters stay in place in browsers without a modal dialog.
- `rel="nofollow"` on option links from the SEO threshold, `data-of-debug-*` attributes in debug mode.
- Everything works without JavaScript; right-to-left stores mirror the layout; no Tailwind rebuild needed.
