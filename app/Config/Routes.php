<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');
$routes->get('/login', 'Login::index');
$routes->post('/login', 'Login::attempt');
$routes->post('/logout', 'Login::logout');

// Let op: (:any) is greedy en matcht ook slashes, dus de specifieke /photo, /status
// en /move routes MOETEN vóór de generieke /d/{nummer}-{token} route staan — anders
// vangt die ze allemaal af (CI4 matcht routes in registratievolgorde).
$routes->post('/d/(:num)-(:any)/photo', 'Box::photo/$1/$2');
$routes->post('/d/(:num)-(:any)/status', 'Box::status/$1/$2');
$routes->post('/d/(:num)-(:any)/move', 'Box::move/$1/$2');
$routes->get('/d/(:num)-(:any)', 'Box::show/$1/$2');
$routes->post('/d/(:num)-(:any)', 'Box::store/$1/$2');

$routes->get('/foto/(:num)', 'Photo::show/$1');

$routes->get('/verplaats', 'Move::start');
$routes->post('/verplaats', 'Move::go');
$routes->get('/verplaats/(:any)/scan', 'Move::scan/$1');
$routes->post('/verplaats/(:any)/scan', 'Move::doScan/$1');
$routes->post('/verplaats/(:any)/sluit', 'Move::finish/$1');

$routes->get('/zoek', 'Search::index');

$routes->get('/overzicht', 'Overview::index');
$routes->get('/overzicht/lijst', 'Overview::list');

$routes->get('/import', 'Csv::importForm');
$routes->post('/import', 'Csv::import');
$routes->get('/export', 'Csv::export');
