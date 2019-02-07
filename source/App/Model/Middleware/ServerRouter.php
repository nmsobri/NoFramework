<?php declare( strict_types=1 );

namespace Aemulus\App\Model\Middleware;

use Aemulus\Lib\Utility;
use League\Container\Container;
use Phroute\Phroute\Dispatcher;
use Aemulus\Lib\RouterResolver;
use Phroute\Phroute\RouteCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Middlewares\Utils\Traits\HasResponseFactory;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Symfony\Component\HttpFoundation\Response as HTTPCODE;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;

class ServerRouter implements MiddlewareInterface {
    use HasResponseFactory;

    private $router;
    private $container;

    public function __construct( RouteCollector $router, Container $container ) {
        $this->router = $router;
        $this->container = $container;
    }


    public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ): ResponseInterface {
        try {
            $resolver = new RouterResolver( $this->container );
            $dispatcher = new Dispatcher( $this->router->getData(), $resolver );
            $response = $dispatcher->dispatch( $request->getMethod(), Utility::getPathInfo( $request ) );

        } catch ( HttpRouteNotFoundException $e ) {
            $jsonData = array(
                'error' => [
                    'code' => HTTPCODE::HTTP_NOT_FOUND,
                    'message' => 'Not Found'
                ]
            );

            return new JsonResponse( $jsonData, HTTPCODE::HTTP_NOT_FOUND );

        } catch ( HttpMethodNotAllowedException $e ) {
            $jsonData = array(
                'error' => [
                    'code' => HTTPCODE::HTTP_METHOD_NOT_ALLOWED,
                    'message' => 'Method Not Allowed'
                ]
            );

            return new JsonResponse( $jsonData, HTTPCODE::HTTP_METHOD_NOT_ALLOWED );
        }

        return $response;
    }
}