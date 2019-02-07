<?php declare( strict_types=1 );

define( 'ROOT_DIR', dirname( dirname( __DIR__ ) ) );

require_once ROOT_DIR . '/vendor/autoload.php';

use Whoops\Run;
use Dotenv\Dotenv;
use Monolog\Logger;
use MongoDB\Client;
use Zend\Diactoros\Response;
use Aemulus\Lib\ServerEmitter;
use League\Container\Container;
use Doctrine\DBAL\Configuration;
use Phroute\Phroute\RouteParser;
use Doctrine\DBAL\DriverManager;
use Middlewares\Utils\Dispatcher;
use Monolog\Handler\StreamHandler;
use Aemulus\Lib\ConditionalEmitter;
use Phroute\Phroute\RouteCollector;
use Monolog\Formatter\LineFormatter;
use Whoops\Handler\PrettyPageHandler;
use Tuupola\Middleware\CorsMiddleware;
use Zend\Diactoros\ServerRequestFactory;
use Tuupola\Middleware\JwtAuthentication;
use Zend\Diactoros\Response\JsonResponse;
use League\Container\ReflectionContainer;
use League\Container\ContainerAwareInterface;
use Aemulus\App\Model\Middleware\ServerRouter;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Symfony\Component\HttpFoundation\Response as HTTPCODE;


##########################################################################################
#################################### Emitter #############################################
##########################################################################################
$emitter_stack = new ServerEmitter( new EmitterStack() );
$emitter_stack->push( new SapiEmitter() );
$emitter_stack->push( new ConditionalEmitter( new SapiStreamEmitter() ) );


##########################################################################################
############## #Request instance (use this instead of $_GET, $_POST, etc) ################
##########################################################################################
$request = ServerRequestFactory::fromGlobals( $_FILES );


##########################################################################################
############################## Dotenv initialization #####################################
##########################################################################################
if ( file_exists( ROOT_DIR . '/system/data/cfg/.env' ) !== true ) {
    $json = array(
        'error' => [
            'code' => HTTPCODE::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Missing .env file inside cfg/ (please rename .env.*.dist)'
        ]
    );

    $response = new JsonResponse( $json, HTTPCODE::HTTP_INTERNAL_SERVER_ERROR );
    $emitter_stack->emit( $response );
}

$dotenv = new Dotenv( ROOT_DIR . '/system/data/cfg/' );
$dotenv->load();


##########################################################################################
################################## Error handler #########################################
##########################################################################################
$error = new Run;

if ( $_ENV['MODE'] === 'dev' ) {
    $error->pushHandler( new PrettyPageHandler() );
} else {
    $error->pushHandler( function ( \Exception $e ) use ( $request, $emitter_stack ): Response {
        # Log error
        $logger = new Logger( 'Moridaru' );
        $formatter = new LineFormatter( null, null, false, true );

        $handler = new StreamHandler( ROOT_DIR . '/system/data/log/app.log', Logger::CRITICAL );
        $handler->setFormatter( $formatter );

        $logger->pushHandler( $handler );
        $logger->addCritical( $e->getMessage(), array( 'File' => $e->getFile(), 'Line' => $e->getLine() ) );

        # Using the pretty error handler in production is likely a bad idea
        $json = array( 'error' => [ 'code' => HTTPCODE::HTTP_INTERNAL_SERVER_ERROR, 'message' => 'Internal server error.' ] );
        $response = new JsonResponse( $json, HTTPCODE::HTTP_INTERNAL_SERVER_ERROR );
        $emitter_stack->emit( $response );
    } );
}
$error->register();


##########################################################################################
################################### Container ############################################
##########################################################################################
$container = new Container();

$container->share( 'request', $request );

$container->share( 'response', Response::class );

$container
    ->share( 'ai_postgres', DriverManager::getConnection(
        array(
            'url' => sprintf( '%s://%s:%s@%s:%s/%s',
                $_ENV['ad_divergence_db_driver'], $_ENV['ai_divergence_db_user'],
                $_ENV['ai_divergence_db_pass'], $_ENV['ai_divergence_db_host'],
                $_ENV['ai_divergence_db_port'], $_ENV['ai_divergence_db_name']
            )
        ),
        new Configuration() )
    );

$container
    ->share( 'ai_mongo', new Client(
            sprintf( 'mongodb://%s:%s@%s:%s',
                $_ENV['ai_mongo_user'], $_ENV['ai_mongo_pass'],
                $_ENV['ai_mongo_host'], $_ENV['ai_mongo_port']
            )
        )
    );

$container->delegate( ( new ReflectionContainer )->cacheResolutions() );

# Call setContainer() method on any class that implement ContainerAwareInterface interface
$container->inflector( ContainerAwareInterface::class )->invokeMethod( 'setContainer', [ $container ] );


##########################################################################################
#################################### Router ##############################################
##########################################################################################
$router = new RouteCollector( new RouteParser );
include ROOT_DIR . '/system/boot/route.php';


##########################################################################################
################################## Middleware ############################################
##########################################################################################


$middlewares = array(
    new CorsMiddleware( [
        'origin' => [ '*' ],
        'methods' => [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ],
        'headers.allow' => [ 'Content-Type', 'X-Requested-With' ],
        'headers.expose' => [],
        'credentials' => false,
        'cache' => 0,
    ] ),
    /*
    new JwtAuthentication( [
        'secret' => getenv( 'app_secret' ),
        'secure' => false,
        'path' => [ '/' ],
        'ignore' => [ '(.+)?/v1/auth/login' ],
        'error' => function ( $response, $arguments ) {
            $json = [
                'error' => [
                    'code' => HTTPCODE::HTTP_UNAUTHORIZED,
                    'message' => $arguments['message']
                ]
            ];

            $body = $response->getBody();
            $body->write( json_encode( $json ) );
            return $response->withHeader( 'Content-Type', 'application/json' )->withBody( $body );
        }
    ] ), */
    new ServerRouter( $router, $container ),
);


##########################################################################################
################################## Dispatcher ############################################
##########################################################################################
$dispatcher = new Dispatcher( $middlewares );
$response = $dispatcher->dispatch( $request );


##########################################################################################
#################################### Emitter #############################################
##########################################################################################
$emitter_stack->emit( $response );