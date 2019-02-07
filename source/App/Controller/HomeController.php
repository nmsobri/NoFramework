<?php declare( strict_types=1 );

namespace Aemulus\App\Controller;

use Aemulus\Lib\AppController;
use Psr\Http\Message\ResponseInterface;
use Aemulus\App\Model\Service\HomeService;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AppController {
    private $home_service;

    public function __construct( HomeService $user_service ) {
        $this->home_service = $user_service;
    }

    public function index(): ResponseInterface {
        $json = $this->home_service->index();
        return $this->json( $json, Response::HTTP_OK );
    }

}