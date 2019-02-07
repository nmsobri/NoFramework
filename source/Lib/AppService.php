<?php declare( strict_types=1 );

namespace Aemulus\Lib;

use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;

abstract class AppService implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __get( $name ) {
        if ( $this->container->has( $name ) ) {
            return $this->container->get( $name );
        }

        return null;
    }
}