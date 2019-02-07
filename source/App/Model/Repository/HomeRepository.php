<?php declare( strict_types=1 );

namespace Aemulus\App\Model\Repository;

use Aemulus\App\Model\Entity\Postgres\HomeModel;

class HomeRepository {
    private $home_model;

    public function __construct( HomeModel $home_model ) {
        $this->home_model = $home_model;
    }

    public function index(): ?string {
        $result = $this->home_model->index();
        return $result;
    }
}