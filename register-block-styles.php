<?php

/**
 * Plugin Name: Register Block Styles
 * Version: 0.1.0
 */

class Register_Block_Styles
{
    private static $version = '0.1.0';

    private static function wp_enqueue_block_style($blockName, $handle, $relativeFile, $deps)
    {
        $dir = get_stylesheet_directory() . $relativeFile;
        if (!file_exists($dir)) {
            return;
        }

        $src = get_stylesheet_directory_uri() . $relativeFile;

        // Upgrade note:
        // We should be able to use wp_enqueue_style here to only load the block style when used, but this bug:
        // https://core.trac.wordpress.org/ticket/55184 means that we have to use wp_enqueue_block_style to attach the style
        // to the block instead.  It will always load when the block is used, but that's better than not loading at all or
        // loading even when the block is not used.
        // More bug info: https://www.damiencarbery.com/2022/04/add-custom-block-styles/

        wp_enqueue_block_style($blockName, [
            'handle' => $handle,
            'src' => $src,
            'deps' => $deps,
            'ver' => self::$version,
        ]);
    }

    private static function register_block_style($blockName, $config, string $stylesDir)
    {
        $deps = array_key_exists('deps', $config) ? $config['deps'] : [];
        $styleName = $config['name'];
        $relativeFile = "/$stylesDir/$blockName/$styleName.css";
        $themeName = wp_get_theme()->get('Name');
        $handle = "$themeName/block-styles/$blockName/$styleName";

        self::wp_enqueue_block_style($blockName, $handle, $relativeFile, $deps);
    }

    private static function register_shared_block_style($blockName, $config, string $stylesDir)
    {
        $deps = array_key_exists('deps', $config) ? $config['deps'] : [];
        $styleName = $config['name'];
        $relativeFile = "/$stylesDir/$styleName.css";
        $themeName = wp_get_theme()->get('Name');
        $handle = "$themeName/block-styles/$styleName";

        self::wp_enqueue_block_style($blockName, $handle, $relativeFile, $deps);
    }

    private static function get_settings()
    {
        $dir = get_stylesheet_directory();
        $settingsFile = $dir . '/block-styles.json';

        if (!file_exists($settingsFile)) {
            return;
        }

        return json_decode(file_get_contents($settingsFile), true);
    }

    private static function register_block_styles()
    {
        $dir = get_stylesheet_directory();
        $settingsFile = $dir . '/block-styles.json';

        if (!file_exists($settingsFile)) {
            return;
        }

        $settings = self::get_settings();
        $stylesDir = $settings['stylesDir'] ?: 'build/block-styles';

        foreach ($settings['blocks'] as $blockName => $blockConfig) {
            // register shared block style
            if (array_key_exists('registerShared', $blockConfig)) {
                foreach ($blockConfig['registerShared'] as $styleConfig) {
                    self::register_shared_block_style($blockName, $styleConfig, $stylesDir);
                }
            }

            // register block style
            if (array_key_exists('register', $blockConfig)) {
                foreach ($blockConfig['register'] as $styleConfig) {
                    self::register_block_style($blockName, $styleConfig, $stylesDir);
                }
            }
        }

        return $settings;
    }

    public static function init_hook()
    {
        self::register_block_styles();
    }

    public static function enqueue_block_editor_assets_hook()
    {
        $settings = self::get_settings();
        if (!$settings) {
            return;
        }

        $blocks = $settings['blocks'];

        $blockStylesUnregister = array_filter(array_map(function ($block) {
            if (!array_key_exists('unregister', $block)) {
                return;
            }

            return $block['unregister'];
        }, $blocks));

        $blockStylesRegister = [];
        foreach($blocks as $blockName => $blockConfig) {
            $stylesRegister = [];
            if (array_key_exists('register', $blockConfig)) {
                foreach($blockConfig['register'] as $styleConfig) {
                    $styleName = $styleConfig['name'];
                    $themeName = wp_get_theme()->get('Name');
                    $textDomain = wp_get_theme()->get('TextDomain');
                    $style = $styleConfig;
                    $style['label'] = __($styleConfig['label'], $textDomain);
                    $style['style'] = "$themeName/block-styles/$blockName/$styleName";
                    $stylesRegister[] = $style;

                }
            }
            if (array_key_exists('registerShared', $blockConfig)) {
                foreach($blockConfig['registerShared'] as $styleConfig) {
                    $styleName = $styleConfig['name'];
                    $themeName = wp_get_theme()->get('Name');
                    $textDomain = wp_get_theme()->get('TextDomain');
                    $style = $styleConfig;
                    $style['label'] = __($styleConfig['label'], $textDomain);
                    $style['style'] = "$themeName/block-styles/$styleName";
                    $stylesRegister[] = $style;

                }
            }
            if (count($stylesRegister)) {
                $blockStylesRegister[$blockName] = $stylesRegister;
            }
        }

        wp_enqueue_script('register-block-styles-script', plugin_dir_url(__FILE__) . 'build/index.js', [], self::$version, true);
        wp_localize_script('register-block-styles-script', 'pluginRegisterBlockStylesScriptData', [
            'blockStylesUnregister' => $blockStylesUnregister,
            'blockStylesRegister' => $blockStylesRegister,
        ]);
    }
}

add_action('enqueue_block_editor_assets', ['Register_Block_Styles', 'enqueue_block_editor_assets_hook'], 100);
add_filter('init', ['Register_Block_Styles', 'init_hook'], null, 2);
