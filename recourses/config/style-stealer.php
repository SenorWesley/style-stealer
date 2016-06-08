<?php
/* --------------------------------------------------------------------------------------
 *
 * Config file for the StyleStealer (Laravel wrapper) package.
 *
 * -------------------------------------------------------------------------------------- */

return [
    /*
     * The record key holds all the fields to record.
     */
    'record' => [
        /*
         * Get image tags.
         */
        'images',

        /*
         * Styles from CSS.
         */
        'styles' => [
            'font-family',
            'color',
            'background-color',
        ],

        /*
         * Record meta tags (og:image).
         */
        'meta_tags' => true,
    ],

    /*
     * Should we skip the greyscale colors.
     */
    'skip_greyscale' => false,

    /*
     * If we steal styles from CSS, we might skip the CSS Frameworks.
     * Most of the time we don't need there styles.
     * 
     * Recommend to stay on FALSE, because: loading improvements.
     */
    'frameworks' => false,

    /*
     * If we're going to skip frameworks, which are we going to skip?
     *
     * For example: If a known framework in our array is Bootstrap
     * and if one of the CSS file contains the word 'bootstrap', then
     * we will skip that file.
     *
     * Feel free to update this list.
     */
    'known_frameworks' => [
        'bootstrap',
        'basscss',
        'cardinal',
        'foundation',
        'html5-boilerplate',
        'jeet',
        'kickstart',
        'materialize',
        'metro',
        'pure',
        'semantic',
        'simplegrid',
        'skeleton',
        'topcoat',
        'unsemantic',
        'uikit',
    ],
];