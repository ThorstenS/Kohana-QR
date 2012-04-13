<?php defined('SYSPATH') OR die('No direct access allowed.');

Route::set('qr-code', 'qr/<size>/<ecc>(/<text>)', array(
        'controller' => 'Qr'
    ))
	->defaults(array(
		'controller' => 'qr',
		'action'     => 'index',
		'size'       => 7,
		'ecc'        => 'L',
	));