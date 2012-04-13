<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * PHP QR Code encoder
 *
 * Input splitting classes
 *
 * Based on libqrencode C library distributed under LGPL 2.1
 * Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * The following data / specifications are taken from
 * "Two dimensional symbol -- QR-code -- Basic Specification" (JIS X0510:2004)
 *  or
 * "Automatic identification and data capture techniques -- 
 *  QR Code 2005 bar code symbology specification" (ISO/IEC 18004:2006)
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
abstract class Kohana_Qr_Split extends Qr {

    public $dataStr = '';
    public $input;
    public $modeHint;

    //----------------------------------------------------------------------
    public function __construct($dataStr, $input, $modeHint) 
    {
        $this->dataStr  = $dataStr;
        $this->input    = $input;
        $this->modeHint = $modeHint;
    }
    
    //----------------------------------------------------------------------
    public static function isdigitat($str, $pos)
    {    
        if ($pos >= strlen($str))
            return false;
        
        return ((ord($str[$pos]) >= ord('0'))&&(ord($str[$pos]) <= ord('9')));
    }
    
    //----------------------------------------------------------------------
    public static function isalnumat($str, $pos)
    {
        if ($pos >= strlen($str))
            return false;
            
        return (Qr_Input::lookAnTable(ord($str[$pos])) >= 0);
    }
    
    //----------------------------------------------------------------------
    public function identifyMode($pos)
    {
        if ($pos >= strlen($this->dataStr)) 
            return self::$QR_MODE_NUL;
            
        $c = $this->dataStr[$pos];
        
        if(Qr_Split::isdigitat($this->dataStr, $pos)) {
            return self::$QR_MODE_NUM;
        } else if(Qr_Split::isalnumat($this->dataStr, $pos)) {
            return self::$QR_MODE_AN;
        } else if($this->modeHint == self::$QR_MODE_KANJI) {
        
            if ($pos+1 < strlen($this->dataStr)) 
            {
                $d = $this->dataStr[$pos+1];
                $word = (ord($c) << 8) | ord($d);
                if(($word >= 0x8140 && $word <= 0x9ffc) || ($word >= 0xe040 && $word <= 0xebbf)) {
                    return self::$QR_MODE_KANJI;
                }
            }
        }

        return self::$QR_MODE_8;
    } 
    
    public function eatNum()
    {
        $ln = Qr_Spec::lengthIndicator(self::$QR_MODE_NUM, $this->input->getVersion());

        $p = 0;
        while(Qr_Split::isdigitat($this->dataStr, $p)) {
            $p++;
        }
        
        $run = $p;
        $mode = $this->identifyMode($p);
        
        if($mode == self::$QR_MODE_8) {
            $dif = Qr_Input::estimateBitsModeNum($run) + 4 + $ln
                 + Qr_Input::estimateBitsMode8(1)         // + 4 + l8
                 - Qr_Input::estimateBitsMode8($run + 1); // - 4 - l8
            if($dif > 0) {
                return $this->eat8();
            }
        }
        if($mode == self::$QR_MODE_AN) {
            $dif = Qr_Input::estimateBitsModeNum($run) + 4 + $ln
                 + Qr_Input::estimateBitsModeAn(1)        // + 4 + la
                 - Qr_Input::estimateBitsModeAn($run + 1);// - 4 - la
            if($dif > 0) {
                return $this->eatAn();
            }
        }
        
        $ret = $this->input->append(self::$QR_MODE_NUM, $run, str_split($this->dataStr));
        if($ret < 0)
            return -1;

        return $run;
    }
    
    //----------------------------------------------------------------------
    public function eatAn()
    {
        $la = Qr_Spec::lengthIndicator(self::$QR_MODE_AN,  $this->input->getVersion());
        $ln = Qr_Spec::lengthIndicator(self::$QR_MODE_NUM, $this->input->getVersion());

        $p = 0;
        
        while(Qr_Split::isalnumat($this->dataStr, $p)) {
            if(Qr_Split::isdigitat($this->dataStr, $p)) {
                $q = $p;
                while(Qr_Split::isdigitat($this->dataStr, $q)) {
                    $q++;
                }
                
                $dif = Qr_Input::estimateBitsModeAn($p) // + 4 + la
                     + Qr_Input::estimateBitsModeNum($q - $p) + 4 + $ln
                     - Qr_Input::estimateBitsModeAn($q); // - 4 - la
                     
                if($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else {
                $p++;
            }
        }

        $run = $p;

        if(!Qr_Split::isalnumat($this->dataStr, $p)) {
            $dif = Qr_Input::estimateBitsModeAn($run) + 4 + $la
                 + Qr_Input::estimateBitsMode8(1) // + 4 + l8
                  - Qr_Input::estimateBitsMode8($run + 1); // - 4 - l8
            if($dif > 0) {
                return $this->eat8();
            }
        }

        $ret = $this->input->append(self::$QR_MODE_AN, $run, str_split($this->dataStr));
        if($ret < 0)
            return -1;

        return $run;
    }
    
    //----------------------------------------------------------------------
    public function eat8()
    {
        $la = Qr_Spec::lengthIndicator(self::$QR_MODE_AN, $this->input->getVersion());
        $ln = Qr_Spec::lengthIndicator(self::$QR_MODE_NUM, $this->input->getVersion());

        $p = 1;
        $dataStrLen = strlen($this->dataStr);
        
        while($p < $dataStrLen) {
            
            $mode = $this->identifyMode($p);
            if($mode == self::$QR_MODE_KANJI) {
                break;
            }
            if($mode == self::$QR_MODE_NUM) {
                $q = $p;
                while(Qr_Split::isdigitat($this->dataStr, $q)) {
                    $q++;
                }
                $dif = Qr_Input::estimateBitsMode8($p) // + 4 + l8
                     + Qr_Input::estimateBitsModeNum($q - $p) + 4 + $ln
                     - Qr_Input::estimateBitsMode8($q); // - 4 - l8
                if($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else if($mode == self::$QR_MODE_AN) {
                $q = $p;
                while(Qr_Split::isalnumat($this->dataStr, $q)) {
                    $q++;
                }
                $dif = Qr_Input::estimateBitsMode8($p)  // + 4 + l8
                     + Qr_Input::estimateBitsModeAn($q - $p) + 4 + $la
                     - Qr_Input::estimateBitsMode8($q); // - 4 - l8
                if($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else {
                $p++;
            }
        }

        $run = $p;
        $ret = $this->input->append(self::$QR_MODE_8, $run, str_split($this->dataStr));
        
        if($ret < 0)
            return -1;

        return $run;
    }

    //----------------------------------------------------------------------
    public function splitString()
    {
        while (strlen($this->dataStr) > 0)
        {
            if($this->dataStr == '')
                return 0;

            $mode = $this->identifyMode(0);
            
            switch ($mode) {
                case self::$QR_MODE_NUM: $length = $this->eatNum(); break;
                case self::$QR_MODE_AN:  $length = $this->eatAn(); break;
                case self::$QR_MODE_KANJI:
                    if ($hint == self::$QR_MODE_KANJI)
                            $length = $this->eatKanji();
                    else    $length = $this->eat8();
                    break;
                default: $length = $this->eat8(); break;
            
            }

            if($length == 0) return 0;
            if($length < 0)  return -1;
            
            $this->dataStr = substr($this->dataStr, $length);
        }
    }
    
    //----------------------------------------------------------------------
    public static function splitStringToQRinput($string, Qr_Input $input, $modeHint, $casesensitive = true)
    {
        if(is_null($string) || $string == '\0' || $string == '') {
            throw new Qr_Exception('empty string!!!');
        }

        $split = new Qr_Split($string, $input, $modeHint);
        
        if(!$casesensitive)
            $split->toUpper();
            
        return $split->splitString();
    }
}