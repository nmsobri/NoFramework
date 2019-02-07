<?php declare( strict_types=1 );

use Aemulus\App\Controller\HomeController;

##########################################################
#################### @Route filters ######################
##########################################################

##########################################################
################### @Route rules #########################
##########################################################

# Route to /v1
$router->group( [ 'prefix' => '/v1' ], function ( $router ): void {

    # POST to /v1/auth/login
    $router->get( '/', [ HomeController::class, 'index' ] );

} );