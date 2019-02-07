<?php
declare( strict_types=1 );

namespace Aemulus\App\Model\Middleware;

use Monolog\Logger;
use Aemulus\Lib\Utility;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResponseTime implements MiddlewareInterface {
    const HEADER = 'X-Response-Time';

    public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ): ResponseInterface {
        $server = $request->getServerParams();
        $startTime = $server['REQUEST_TIME_FLOAT'] ?? microtime( true );
        $response = $handler->handle( $request );
        $pathInfo = Utility::getPathInfo( $request );
        $exclude_path = [ '/favicon.ico' ];

        # Log error
        $logger = new Logger( 'Moridaru' );
        $formatter = new LineFormatter( "Request Time: %datetime%, %message% %context% %extra%\n", null, false, true );

        $handler = new StreamHandler( ROOT_DIR . '/system/data/log/benchmark.log', Logger::INFO );
        $handler->setFormatter( $formatter );
        $logger->pushHandler( $handler );

        $message = sprintf(
            'Path: %s %s, Response Time: %2.3fms',
            $request->getMethod(),
            $pathInfo,
            ( microtime( true ) - $startTime ) * 1000
        );

        if ( preg_match( '#lines/multi/colors/\d+#', $pathInfo ) ) {
            $response_data = json_decode( $response->getBody()->getContents(), true );
            $total_row = count( $response_data['content']['data'] );
            $message .= sprintf( ', Mongo rows: %s', $total_row );
        }

        if ( !in_array( $pathInfo, $exclude_path ) ) {
            $logger->info( $message );
        }

        return $response;
    }
}