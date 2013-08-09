<?php defined('SYSPATH') or die('No direct script access.');

/**
 * QR module to create QR codes
 *
 * @package    QR
 */
class Controller_Qr extends Controller {

    public function action_index()
    {
        $size   = $this->request->param('size');
        $ecc    = $this->request->param('ecc');

        $config = Kohana::$config->load('qr');

        switch ($config->set_data_method)
        {
            case 'POST':
                $text   = $this->request->post('text');
                break;
            case 'GET':
                $text   = $this->request->query('text');
                break;
            default:
                $text   = $this->request->param('text');
                break;
        }

        if (empty($text))
        {
            throw new Qr_Exception('Text must not be empty');
        }

        $qr = QR::factory($size, $ecc);

        $this->response->headers('Content-Type', 'image/png');
        $this->response->body($qr->render($text));
    }
}