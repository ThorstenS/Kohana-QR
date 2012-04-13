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
     *@var $_valid_eccs    Valid options for ecc parameter
     */
    protected $_valid_eccs = array('L', 'M', 'Q', 'H');
    
    /*
     *@var $ecc    ECC correction leven =>  L, M, Q or H
     */
    protected $ecc = 'L';
    protected $level = 3;
    
    protected static $QR_CACHEABLE = false;
    protected static $QR_CACHE_DIR;
    protected static $QR_LOG_DIR;
    protected static $QR_FIND_BEST_MASK = true;
    protected static $QR_FIND_FROM_RANDOM = false;
    protected static $QR_DEFAULT_MASK = 2;
    protected static $QR_MODE_NUL = -1;
    protected static $QR_MODE_NUM = 0;
    protected static $QR_MODE_AN = 1;
    protected static $QR_MODE_8  = 2;
    protected static $QR_MODE_KANJI = 3;
    protected static $QR_MODE_STRUCTURE = 4;
    protected static $QR_ECLEVEL_L = 0;
    protected static $QR_ECLEVEL_M = 1;
    protected static $QR_ECLEVEL_Q = 2;
    protected static $QR_ECLEVEL_H = 3;
    protected static $QR_IMAGE = true;
    protected static $STRUCTURE_HEADER_BITS = 20;
    protected static $MAX_STRUCTURED_SYMBOLS = 16;
    protected static $N1 = 3;
    protected static $N2 = 3;
    protected static $N3 = 40;
    protected static $N4 = 10;
    protected static $QRSPEC_VERSION_MAX = 40;
    protected static $QRSPEC_WIDTH_MAX = 177;
    protected static $QRCAP_WIDTH = 0;
    protected static $QRCAP_WORDS = 1;
    protected static $QRCAP_REMINDER = 2;
    protected static $QRCAP_EC = 3;
    
    /**
     * Returns an instance of QR
     *
     *     $qr = Qr::instance($size = 4, $ecc = 'L');
     *
     * @param   int  Size of QR code
     * @param   string  ECC level
     * @return  Qr object
     */
    public static function factory($size = 4, $ecc = 'L')
    {
        return new Qr($size, $ecc);
    }
    
    /**
     * Constructor for QR class
     *
     * @param   int  Size of QR code
     * @param   string  ECC level
     * @throws  Qr_Exception
     */
    public function __construct($size = 4, $ecc = 'L')
    {
        $this->_valid_sizes = range(1,40);
        
        self::$QR_CACHE_DIR = APPPATH.'/cache/'.DIRECTORY_SEPARATOR;
        self::$QR_LOG_DIR = APPPATH.'/logs/';
    
        if ( ! in_array($size, $this->_valid_sizes))
        {
            throw new Qr_Exception('Invalid QR code size');
        }
        
        if ( ! in_array($ecc, $this->_valid_eccs))
        {
            throw new Qr_Exception('Invalid QR ecc level');
        }
        
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
        return QR_Code::svg($data, false, $this->ecc, $this->size);
    }
    
}