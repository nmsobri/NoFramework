<?php declare( strict_types=1 );

namespace Aemulus\App\Model\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class XhprofMysql implements MiddlewareInterface {

    public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ): ResponseInterface {
        $request = $request->withQueryParams( $request->getQueryParams() + [ '_profile' => '1' ] );
        $_GET = $request->getQueryParams();
        include ROOT_DIR . '/system/xhprof/mysql/start.php';
        $response = $handler->handle( $request );
        include ROOT_DIR . '/system/xhprof/mysql/finish.php';
        return $response;
    }
}