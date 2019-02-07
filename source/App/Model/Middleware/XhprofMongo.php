<?php declare( strict_types=1 );

namespace Aemulus\App\Model\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class XhprofMongo implements MiddlewareInterface {

    public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ): ResponseInterface {
        include ROOT_DIR . '/system/xhprof/mongo/start.php';
        $response = $handler->handle( $request );
        include ROOT_DIR . '/system/xhprof/mongo/finish.php';
        return $response;
    }
}