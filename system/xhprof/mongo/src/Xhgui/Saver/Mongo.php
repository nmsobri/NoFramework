<?php

require __DIR__ . '/Interface.php';

class Xhgui_Saver_Mongo implements Xhgui_Saver_Interface {
    /**
     * @var \MongoDB\Collection
     */
    private $_collection;

    /**
     * @var \MongoDB\BSON\ObjectId lastProfilingId
     */
    private static $lastProfilingId;

    public function __construct( \MongoDB\Collection $collection ) {
        $this->_collection = $collection;
    }

    public function save( array $data ) {
        $data['_id'] = self::getLastProfilingId();

        return $this->_collection->insertOne( $data, array( 'w' => 0 ) );
    }

    /**
     * Return profiling ID
     * @return \MongoDB\BSON\ObjectId lastProfilingId
     */
    public static function getLastProfilingId() {
        if ( !self::$lastProfilingId ) {
            self::$lastProfilingId = new \MongoDB\BSON\ObjectId();
        }
        return self::$lastProfilingId;
    }
}
