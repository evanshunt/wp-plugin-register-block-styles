/* global pluginRegisterBlockStylesScriptData */

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

export default function registerBlockStyles() {
    const { blockStylesRegister, blockStylesUnregister } = pluginRegisterBlockStylesScriptData;

    /**
     * Unregister styles
     */
    Object.keys(blockStylesUnregister).forEach((blockName) => {
        const styleNames = blockStylesUnregister?.[blockName];
        styleNames.forEach((styleName) => {
            wp.blocks.unregisterBlockStyle(blockName, styleName);
        });
    });

    /**
     * Register new styles
     */
    Object.keys(blockStylesRegister).forEach((blockName) => {
        const styleNames = blockStylesRegister?.[blockName];
        styleNames.forEach((styleConfig) => {
            wp.blocks.registerBlockStyle(blockName, styleConfig);
        });
    });
}
