<?php

require __DIR__ . '/src/Xhgui/Util.php';
require __DIR__ . '/src/Xhgui/Saver.php';

if ( extension_loaded( 'tideways' ) ) {
    $data['profile'] = tideways_disable();
} elseif ( extension_loaded( 'tideways_xhprof' ) ) {
    $data['profile'] = tideways_xhprof_disable();
}

$uri = array_key_exists( 'REQUEST_URI', $_SERVER ) ? $_SERVER['REQUEST_URI'] : null;

if ( empty( $uri ) && isset( $_SERVER['argv'] ) ) {
    $cmd = basename( $_SERVER['argv'][0] );
    $uri = $cmd . ' ' . implode( ' ', array_slice( $_SERVER['argv'], 1 ) );
}

$time = array_key_exists( 'REQUEST_TIME', $_SERVER ) ? $_SERVER['REQUEST_TIME'] : time();

# In some cases there is comma instead of dot
$delimiter = ( strpos( $_SERVER['REQUEST_TIME_FLOAT'], ',' ) !== false ) ? ',' : '.';
$requestTimeFloat = explode( $delimiter, $_SERVER['REQUEST_TIME_FLOAT'] );

if ( !isset( $requestTimeFloat[1] ) ) {
    $requestTimeFloat[1] = 0;
}

if ( Xhgui_Config::read( 'save.handler' ) === 'mongodb' ) {
    #$requestTs = new \MongoDate($time);
    $requestTs = new \MongoDB\BSON\UTCDateTime( $time * 1000 );
    #$requestTsMicro = new \MongoDate($requestTimeFloat[0], $requestTimeFloat[1]);
    $requestTsMicro = ( new \MongoDB\BSON\UTCDateTime( $_SERVER['REQUEST_TIME_FLOAT'] * 1000 ) )->toDateTime()->format( 'U.u' );
} else {
    $requestTs = array( 'sec' => $time, 'usec' => 0 );
    $requestTsMicro = array( 'sec' => $requestTimeFloat[0], 'usec' => $requestTimeFloat[1] );
}

$data['meta'] = array(
    'url' => $uri,
    'SERVER' => $_SERVER,
    'get' => $_GET,
    'env' => $_ENV,
    'simple_url' => Xhgui_Util::simpleUrl( $uri ),
    'request_ts' => $requestTs,
    'request_ts_micro' => $requestTsMicro,
    'request_date' => date( 'Y-m-d', $time ),
);

try {
    $config = Xhgui_Config::all();
    $config += array( 'db.options' => array() );
    $saver = Xhgui_Saver::factory( $config );
    $saver->save( $data );
} catch ( Exception $e ) {
    error_log( 'xhgui - ' . $e->getMessage() );
}
