<?php namespace Campuslane\Elasticsearch;

use Illuminate\Support\Facades\Facade;

class ElasticClientFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'elasticclient'; }

}