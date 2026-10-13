/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 *
 * Tailwind source registered through `bin/magento hyva:config:generate` so a theme build can scan
 * OpenFilter templates. Nothing in OpenFilter depends on this file for correctness: all structural
 * CSS ships as a static file (see docs/hyva-integration.md in iranimij/openfilter).
 */
module.exports = {
    content: [
        '../templates/**/*.phtml'
    ]
};
