<?php
/**
 * Plugin Name: Google Font Fix
 * Plugin URI: https://github.com/zjhzxhz/google-font-fix
 * Description: Use 360 Open Fonts Service to replace Google's for Chinese users.
 * Author: 谢浩哲
 * Author URI: http://zjhzxhz.com
 * Version: 1.3.2
 * License: GPL v2.0
 */

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
require_once(PLUGIN_PATH . 'geo/geoip.inc.php');

function google_apis_fix($buffer) {
    $geoData     = geoip_open(PLUGIN_PATH . 'geo/GeoIP.dat', GEOIP_STANDARD);
    $countryCode = geoip_country_code_by_addr($geoData, $_SERVER['REMOTE_ADDR']);
    geoip_close($geoData);
    
    if( $countryCode != 'CN' ) {
        return $buffer;
    }

    /*
    <link rel="stylesheet" id="open-sans-css" href="//fonts.googleapis．com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&amp;subset=latin%2Clatin-ext&amp;ver=3.9.2" type="text/css" media="all">
    <script type="text/javascript" src="http://ajax.googleapis．com/ajax/libs/jquery/1.6/jquery.min.js"></script>
    */
    $regexp = "/<(link|script)([^<>]+)>/i";
    $buffer = preg_replace_callback(
        $regexp,
        "str_handler1",
        $buffer
    );

    /*
    @import url(http://fonts.googleapis．com/css?family=Roboto+Condensed:regular);
    @import url(http://fonts.googleapis．com/css?family=Merriweather:300,300italic,700,700italic);
    */
    $regexp = "/@import\s+url\([^\(\)]+\);?/i";
    $buffer = preg_replace_callback(
        $regexp,
        "useso_take_over_google_str_handler",
        $buffer
    );

    /*
    google fonts imported by 'Web Font Loader'
    */
    $webfont_js = USESO_TAKE_OVER_GOOGLE_PLUGIN_URL.'webfont_v1.5.3.js';
    if (is_ssl()) {
        $webfont_js = USESO_TAKE_OVER_GOOGLE_PLUGIN_URL.'webfont_https_v1.5.3.js';
    }
    //$content = str_ireplace('//ajax.googleapis'.'.com/ajax/libs/webfont/1/webfont.js', substr($webfont_js, strpos($webfont_js,'//')), $content);
    $buffer = preg_replace('|//ajax.googleapis'.'.com/ajax/libs/webfont/[\d\.]+/webfont.js|i', substr($webfont_js, strpos($webfont_js,'//')), $buffer);

    /*
    gravatar imgs:
        <img src="http://1.gravatar．com/avatar/11fee321889526d1df2393655f48bd0c?s=26&d=retro&r=g">
        <img src="https://secure.gravatar．com/avatar/06a2950d128ec9faf155e28d9e889baa?s=120">
    */
    /* v1 bak
    $regexp = "/(\d+|www|secure|cn).gravatar.com\/avatar/i";
    $buffer = preg_replace($regexp, 'sdn.geekzu.org/avatar', $buffer);
    */
    $regexp = "/<img([^<>]+)>/i";
    $buffer = preg_replace_callback(
        $regexp,
        "str_handler2",
        $buffer
    );
    
    return $buffer;
}

function str_handler1($matches)
{
    $str = $matches[0];

    if (!is_ssl()) {
        $str = str_ireplace('//fonts.googleapis'.'.com/', '//fonts.useso.com/', $str);
        $str = str_ireplace('//ajax.googleapis'.'.com/', '//ajax.useso.com/', $str);
        $str = str_ireplace('//maps.googleapis'.'.com/', '//maps.google.cn/', $str);

        //$str = str_ireplace('//fonts.googleapis'.'.com/', '//fonts.geekzu.org/', $str);
        //$str = str_ireplace('//ajax.googleapis'.'.com/', '//fdn.geekzu.org/ajax/', $str);
    } else {
        //$str = str_ireplace('//fonts.googleapis'.'.com/', '//fonts.lug.ustc.edu.cn/', $str);
        //$str = str_ireplace('//ajax.googleapis'.'.com/', '//ajax.lug.ustc.edu.cn/', $str);

        $str = str_ireplace('//fonts.googleapis'.'.com/', '//fonts.geekzu.org/', $str);
        $str = str_ireplace('//ajax.googleapis'.'.com/', '//sdn.geekzu.org/ajax/', $str);
        $str = str_ireplace('//maps.googleapis'.'.com/', '//maps.google.cn/', $str);
    }

    /*
    fix below references:
        https://fonts.useso.com/....
        https://ajax.useso.com/....
    */
    if (!is_ssl()) {
        $str = str_ireplace('https://fonts.useso.com/', 'http://fonts.useso.com/', $str);
        $str = str_ireplace('https://ajax.useso.com/', 'http://ajax.useso.com/', $str);
        $str = str_ireplace('https://maps.googleapis.com/', 'http://maps.google.cn/', $str);

        $str = str_ireplace('https://fonts.geekzu.org/', 'http://fonts.geekzu.org/', $str);
        $str = str_ireplace('https://fdn.geekzu.org/ajax/', 'http://fdn.geekzu.org/ajax/', $str);
    } else {
        //...
    }

    return $str;
}

function str_handler2($matches)
{
    $str = $matches[0];

    $regexp = "/(\d+|www|secure|cn|s).gravatar.com\/avatar/i";
    $str = preg_replace($regexp, 'sdn.geekzu.org/avatar', $str);

    return $str;
}

function gff_buffer_start() {
    ob_start("google_apis_fix");
}

function gff_buffer_end() {
    while ( ob_get_level() > 0 ) {
        ob_end_flush();
    }
}

add_action('init', 'gff_buffer_start');
add_action('shutdown', 'gff_buffer_end');
