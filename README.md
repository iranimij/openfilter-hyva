# OpenFilter for Hyvä

The Hyvä storefront of [OpenFilter](https://github.com/iranimij/openfilter), the free MIT layered navigation for Magento 2: filter templates for Hyvä 1.4 and 1.5, one Alpine component for same-URL AJAX (served by Varnish, allowed by Hyvä's CSP), a mobile drawer with "Show N products", sliders and structural CSS that needs no Tailwind rebuild. Everything works without JavaScript.

Install the bundle, which brings this package, the core and the shared base module:

```bash
composer require iranimij/openfilter-hyva-bundle
bin/magento setup:upgrade
bin/magento indexer:reindex catalogsearch_fulltext
```

Markup contract, theme tokens and template overrides: [Storefront](https://github.com/iranimij/openfilter/blob/main/docs/storefront.md). All documentation: [OpenFilter](https://github.com/iranimij/openfilter#readme). Issues: [iranimij/openfilter](https://github.com/iranimij/openfilter/issues).

Licence: MIT, see [LICENSE](LICENSE) and [NOTICE](NOTICE).
