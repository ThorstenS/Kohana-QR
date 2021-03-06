<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * QR module to create QR codes
 *
 * @package    QR
 */
abstract class Kohana_Qr {

    /*
     *@var $_valid_sizes    Valid options for size parameter
     */
    protected $_valid_sizes;

    /*
     *@var $size    Size of QR Code, range 1-10
     */
    protected $size = 4;

    /*
     *@var $frame_size    Frame-Size surrounding the QR code
     */
    protected $frame_size = 4;

    /*
     *@var $_valid_eccs    Valid options for ecc parameter
     */
    protected $_valid_eccs = array('L', 'M', 'Q', 'H');

    /*
     *@var $ecc    ECC correction leven =>  L, M, Q or H
     */
    protected $ecc = 'L';

    /**
     * Returns an instance of QR
     *
     *     $qr = Qr::instance($size = 4, $ecc = 'L');
     *
     * @param   int  Size of QR code
     * @param   string  ECC level
     * @return  Qr object
     */
    public static function factory($size = 4, $ecc = 'L', $frame_size = 4)
    {
        return new Qr($size, $ecc, $frame_size);
    }

    /**
     * Constructor for QR class
     *
     * @param   int  Size of QR code
     * @param   string  ECC level
     * @throws  Qr_Exception
     */
    public function __construct($size = 4, $ecc = 'L', $frame_size = 4)
    {
        $this->_valid_sizes = range(1,40);

        if ( ! in_array($size, $this->_valid_sizes))
        {
            throw new Qr_Exception('Invalid QR code size');
        }

        if ( ! in_array($ecc, $this->_valid_eccs))
        {
            throw new Qr_Exception('Invalid QR ecc level');
        }

        $this->frame_size = abs((int) $frame_size);
        $this->size = $size;
        $this->ecc  = $ecc;
    }

    /**
     * Render data into image
     *
     * @param   string  data
     * @return  image
     */
    public function render($data)
    {
        return QRCode::png($data, false, $this->ecc, $this->size, $this->frame_size);
    }

}