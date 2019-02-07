<?php

# This file should not - under no circumstances - interfere with any other application
if ( !extension_loaded( 'tideways' ) && !extension_loaded( 'tideways_xhprof' ) ) {
    error_log( 'xhgui - Either tideways or tideways_xhprof must be loaded' );
    return;
}

$dir = __DIR__;
require_once $dir . '/src/Xhgui/Config.php';

$configDir = defined( 'XHGUI_CONFIG_DIR' ) ? XHGUI_CONFIG_DIR : $dir . '/config/';

if ( file_exists( $configDir . 'config.php' ) ) {
    Xhgui_Config::load( $configDir . 'config.php' );
} else {
    Xhgui_Config::load( $configDir . 'config.default.php' );
}

unset( $dir, $configDir );

if ( ( !extension_loaded( 'mongo' ) && !extension_loaded( 'mongodb' ) ) && Xhgui_Config::read( 'save.handler' ) === 'mongodb' ) {
    error_log( 'xhgui - Extension mongo not loaded' );
    return;
}

if ( !Xhgui_Config::shouldRun() ) {
    return;
}

if ( !isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime( true );
}

$options = Xhgui_Config::read( 'profiler.options' );

if ( extension_loaded( 'tideways' ) ) {
    tideways_enable( TIDEWAYS_FLAGS_CPU | TIDEWAYS_FLAGS_MEMORY | TIDEWAYS_FLAGS_NO_SPANS, $options );
} elseif ( extension_loaded( 'tideways_xhprof' ) ) {
    tideways_xhprof_enable( TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY );
}