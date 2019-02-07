<?php declare( strict_types=1 );

namespace Aemulus\App\Model\Entity\Postgres;

use Aemulus\Lib\AppModel;

class HomeModel extends AppModel {

    public function index(): ?string {
        return 'Hello world? I guesss???';
    }
}