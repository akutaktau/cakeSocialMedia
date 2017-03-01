<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'CakeSocialMedia',
    ['path' => '/cake-social-media'],
    function (RouteBuilder $routes) {
        $routes->fallbacks('DashedRoute');
    }
);
