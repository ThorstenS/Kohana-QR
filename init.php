<?php defined('SYSPATH') OR die('No direct access allowed.');

require_once(Kohana::find_file('vendor', 'phpqrcode/lib/full/qrlib'));

Route::set('qr-code', 'qr/<size>/<ecc>(/<text>)', array(
        'controller' => 'Qr'
    ))
    ->defaults(array(
        'controller' => 'qr',
        'action'     => 'index',
        'size'       => 7,
        'ecc'        => 'L',
    ));