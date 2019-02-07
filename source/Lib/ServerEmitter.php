<?php declare( strict_types=1 );

namespace Aemulus\Lib;

use Psr\Http\Message\ResponseInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

class ServerEmitter {

    private $emitter_stack;

    public function __construct( EmitterStack $emitter_stack ) {
        $this->emitter_stack = $emitter_stack;
    }

    public function push( EmitterInterface $emitter ) {
        $this->emitter_stack->push( $emitter );
    }

    public function emit( ResponseInterface $response ): void {
        $this->emitter_stack->emit( $response );
        exit;
    }
}