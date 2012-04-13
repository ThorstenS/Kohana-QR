Kohana-QR
============

Kohana-QR is a module to create QR codes.

It is a wrapper for PHP QR Code (http://phpqrcode.sourceforge.net/)


Installation
-----

Copy this module to your modules directory and initialize it in your bootstrap.php

bootstrap.php

	Kohana::modules(array(
        'kohana-qr'           => MODPATH . 'kohana-qr',
    ));


Configuration
-----

You need to specify how to submit data to the module, either my GET, POST or as URL parameter.
Default is POST

Usage
-----
    
    You can either use the QR Class by itself, or request the code as URL.
    
    $qr = QR::factory($size = 10, $ecc = 'H');
    $this->response->headers('Content-Type', 'image/png');
    $this->response->body($qr->render('Hello World!'));
    
    or
    
    Request::factory('qr/10/H')->post('text', 'Hello World!')->execute();