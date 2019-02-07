<?php declare( strict_types=1 );

namespace Aemulus\Lib;

use League\Container\ContainerAwareTrait;
use Zend\Diactoros\Response\JsonResponse;
use League\Container\ContainerAwareInterface;

abstract class AppController implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __get( $name ) {
        if ( $this->container->has( $name ) ) {
            return $this->container->get( $name );
        }

        return null;
    }

    public function json( array $jsonData, int $responseCode ): JsonResponse {
        return new JsonResponse( $jsonData, $responseCode );
    }
}