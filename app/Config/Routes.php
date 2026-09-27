<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Rechten per route via de `access`-filter (handoff.md §2, §7):
//   access:user    — account, verhuizing niet nodig
//   access:any     — account of gast, verhuizing volgt uit het sticker-token
//   access:sjouwer / helper / admin — minstens die rol in de actieve verhuizing

// Publiek
$routes->get('/login', 'Auth::loginForm');
$routes->post('/login', 'Auth::login');
$routes->post('/logout', 'Auth::logout');
$routes->get('/registreren', 'Auth::registerForm');
$routes->post('/registreren', 'Auth::register');
$routes->get('/wachtwoord-vergeten', 'Auth::forgotForm');
$routes->post('/wachtwoord-vergeten', 'Auth::forgot');
$routes->get('/wachtwoord/(:segment)', 'Auth::resetForm/$1');
$routes->post('/wachtwoord/(:segment)', 'Auth::reset/$1');
$routes->get('/verifieer/(:segment)', 'Auth::verify/$1');
$routes->get('/uitnodiging/(:segment)', 'Uitnodiging::show/$1');
$routes->get('/h/(:segment)', 'Handjes::join/$1');
$routes->post('/h/(:segment)', 'Handjes::doJoin/$1');
$routes->get('/medewerker/(:segment)', 'Bedrijf\Medewerker::show/$1');

// Account (verhuizing niet nodig)
$routes->group('', ['filter' => 'access:user'], static function ($routes) {
    $routes->post('/verifieer/opnieuw', 'Auth::resendVerification');
    $routes->post('/uitnodiging/(:segment)', 'Uitnodiging::accept/$1');
    $routes->post('/medewerker/(:segment)', 'Bedrijf\Medewerker::accept/$1');
    $routes->get('/verhuizingen', 'Verhuizingen::index');
    $routes->post('/verhuizingen', 'Verhuizingen::create');
    $routes->post('/verhuizingen/(:num)/kies', 'Verhuizingen::choose/$1');
    $routes->get('/account', 'Account::index');
    $routes->post('/account', 'Account::save');
    $routes->post('/account/wachtwoord', 'Account::password');
    $routes->post('/account/verwijderen', 'Account::delete');
});

// Stickers — let op: (:any) is greedy en matcht ook slashes, dus de specifieke
// /photo, /status, /move en /verwijderen routes MOETEN vóór de generieke staan.
$routes->group('', ['filter' => 'access:any'], static function ($routes) {
    $routes->post('/d/(:num)-(:any)/photo', 'Box::photo/$1/$2');
    $routes->post('/d/(:num)-(:any)/status', 'Box::status/$1/$2');
    $routes->post('/d/(:num)-(:any)/move', 'Box::move/$1/$2');
    $routes->post('/d/(:num)-(:any)/verwijderen', 'Box::delete/$1/$2');
    $routes->get('/d/(:num)-(:any)', 'Box::show/$1/$2');
    $routes->post('/d/(:num)-(:any)', 'Box::store/$1/$2');
});

// Iedereen in de verhuizing, ook sjouwers
$routes->group('', ['filter' => 'access:sjouwer'], static function ($routes) {
    $routes->get('/', 'Home::index');
    $routes->get('/menu', 'Verhuizingen::menu');
    $routes->get('/verplaats', 'Move::start');
    $routes->post('/verplaats', 'Move::go');
    $routes->get('/verplaats/(:segment)/scan', 'Move::scan/$1');
    $routes->post('/verplaats/(:segment)/scan', 'Move::doScan/$1');
    $routes->post('/verplaats/(:segment)/sluit', 'Move::finish/$1');
    $routes->get('/zoek', 'Search::index');
    $routes->get('/overzicht', 'Overview::index');
    $routes->get('/overzicht/lijst', 'Overview::list');
    $routes->get('/lijsten', 'Lijsten::index');
    $routes->get('/lijsten/deur', 'Lijsten::deur');
    $routes->get('/lijsten/kamers', 'Lijsten::kamers');
    $routes->get('/lijsten/controle', 'Lijsten::controle');
});

// Helpers (inpakkers) en admins
$routes->group('', ['filter' => 'access:helper'], static function ($routes) {
    $routes->post('/verplaats/plek/verwijderen', 'Move::hideDestination');
    $routes->get('/foto/(:num)', 'Photo::show/$1');
    $routes->get('/labels', 'Labels::index');
    $routes->post('/labels', 'Labels::generate');
    $routes->get('/labels/print', 'Labels::print');
    $routes->get('/labels/csv', 'Labels::csv');
});

// Alleen admins
$routes->group('', ['filter' => 'access:admin'], static function ($routes) {
    $routes->get('/import', 'Csv::importForm');
    $routes->post('/import', 'Csv::import');
    $routes->get('/export', 'Csv::export');
    $routes->get('/leden', 'Leden::index');
    $routes->post('/leden/uitnodigen', 'Leden::invite');
    $routes->post('/leden/(:num)/rol', 'Leden::setRole/$1');
    $routes->post('/leden/(:num)/verwijderen', 'Leden::remove/$1');
    $routes->post('/uitnodigingen/(:num)/intrekken', 'Leden::revokeInvite/$1');
    $routes->get('/handjes', 'Handjes::index');
    $routes->post('/handjes', 'Handjes::create');
    $routes->get('/handjes/(:num)/qr', 'Handjes::qr/$1');
    $routes->post('/handjes/(:num)/intrekken', 'Handjes::revoke/$1');
    $routes->get('/verhuizing', 'Verhuizingen::settings');
    $routes->post('/verhuizing', 'Verhuizingen::rename');
    $routes->post('/verhuizing/verwijderen', 'Verhuizingen::delete');
});

// Global-admin (whitelabel): alleen op app.boxtracker.nl en alleen voor platform_admins.
$routes->group('beheer', ['filter' => 'beheer'], static function ($routes) {
    $routes->get('/', 'Beheer\Bedrijven::index');
    $routes->post('bedrijven', 'Beheer\Bedrijven::create');
    $routes->get('bedrijven/(:num)', 'Beheer\Bedrijven::show/$1');
    $routes->post('bedrijven/(:num)/status', 'Beheer\Bedrijven::setStatus/$1');
    $routes->post('bedrijven/(:num)/uitnodigen', 'Beheer\Bedrijven::invite/$1');
    $routes->post('uitnodigingen/(:num)/intrekken', 'Beheer\Bedrijven::revokeInvite/$1');
    $routes->post('meekijken/stop', 'Beheer\Meekijken::stop');
    $routes->post('meekijken/(:num)', 'Beheer\Meekijken::start/$1');
});
