<?php defined('SYSPATH') or die('No direct access allowed.');

/*
 * PHP QR Code encoder
 *
 * Main encoder classes.
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
abstract class Kohana_Qr_Encode extends Qr {
    
    public $casesensitive = true;
    public $eightbit = false;
    
    public $version = 0;
    public $size = 3;
    public $margin = 4;
    
    public $structured = 0; // not supported yet
    
//    public $level;
    public $hint = 2;
    
    //----------------------------------------------------------------------
    public static function factory($size = 3, $level = 4)
    {
        $enc = new Qr_Encode();
        $enc->size = $size;
        $enc->margin = 4;
        
        switch ($level.'') {
            case '0':
            case '1':
            case '2':
            case '3':
                    $enc->level = $level;
                break;
            case 'l':
            case 'L':
                    $enc->level = self::$QR_ECLEVEL_L;
                break;
            case 'm':
            case 'M':
                    $enc->level = self::$QR_ECLEVEL_M;
                break;
            case 'q':
            case 'Q':
                    $enc->level = self::$QR_ECLEVEL_Q;
                break;
            case 'h':
            case 'H':
                    $enc->level = self::$QR_ECLEVEL_H;
                break;
        }
        
        return $enc;
    }
    
    //----------------------------------------------------------------------
    public function encode($intext, $outfile = false) 
    {
        $code = new Qr_Code();

        if($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }
        
        if ($outfile!== false) {
            file_put_contents($outfile, join("\n", Qr_Tools::binarize($code->data)));
        } else {
            return Qr_Tools::binarize($code->data);
        }
    }
    
    //----------------------------------------------------------------------
    public function encodeSVG($intext, $outfile = false,$saveandprint=false) 
    {
        try {
        
            ob_start();
            $tab = $this->encode($intext);
            
            $err = ob_get_contents();
            ob_end_clean();
            
            return Qr_Image::svg($tab, $intext);
        
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}