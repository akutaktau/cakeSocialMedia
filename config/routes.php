<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'CakeSocial',
    ['path' => '/cake-social'],
    function (RouteBuilder $routes) {
        $routes->fallbacks('DashedRoute');
    }
);
