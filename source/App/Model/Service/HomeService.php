<?php declare( strict_types=1 );

namespace Aemulus\App\Model\Service;

use Firebase\JWT\JWT;
use Aemulus\Lib\AppService;
use Symfony\Component\HttpFoundation\Response;
use Aemulus\App\Model\Repository\HomeRepository;

class HomeService extends AppService {

    private $home_repository;

    public function __construct( HomeRepository $home_repository ) {
        $this->home_repository = $home_repository;
    }

    public function index(): array {
        $msg = $this->home_repository->index();
        return [ 'msg' => $msg ];
    }
}