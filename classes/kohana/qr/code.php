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
abstract class Kohana_Qr_Code extends Qr {
    
    public $version;
    public $width;
    public $data; 
    
    //----------------------------------------------------------------------
    public function encodeMask(Qr_Input $input, $mask)
    {
        if($input->getVersion() < 0 || $input->getVersion() > self::$QRSPEC_VERSION_MAX) 
        {
            throw new Qr_Exception('wrong version');
        }
        
        if($input->getErrorCorrectionLevel() > self::$QR_ECLEVEL_H) 
        {
            throw new Qr_Exception('wrong level');
        }

        $raw = new Qr_Rawcode($input);
        
        $version = $raw->version;
        $width = Qr_Spec::getWidth($version);
        $frame = Qr_Spec::newFrame($version);
        
        $filler = new Qr_Framefiller($width, $frame);
        
        if(is_null($filler)) 
        {
            return NULL;
        }

        // inteleaved data and ecc codes
        for($i=0; $i<$raw->dataLength + $raw->eccLength; $i++) 
        {
            $code = $raw->getCode();
            $bit = 0x80;
            for($j=0; $j<8; $j++) 
            {
                $addr = $filler->next();
                $filler->setFrameAt($addr, 0x02 | (($bit & $code) != 0));
                $bit = $bit >> 1;
            }
        }
        
        unset($raw);
        
        // remainder bits
        $j = Qr_Spec::getRemainder($version);
        for($i=0; $i<$j; $i++) 
        {
            $addr = $filler->next();
            $filler->setFrameAt($addr, 0x02);
        }
        
        $frame = $filler->frame;
        unset($filler);
        
        // masking
        $maskObj = new Qr_Mask();
        if($mask < 0) {
        
            if (self::$QR_FIND_BEST_MASK) {
                $masked = $maskObj->mask($width, $frame, $input->getErrorCorrectionLevel());
            } else {
                $masked = $maskObj->makeMask($width, $frame, (intval(self::$QR_DEFAULT_MASK) % 8), $input->getErrorCorrectionLevel());
            }
        } else {
            $masked = $maskObj->makeMask($width, $frame, $mask, $input->getErrorCorrectionLevel());
        }
        
        if($masked == NULL) {
            return NULL;
        }
        
        $this->version = $version;
        $this->width = $width;
        $this->data = $masked;
        
        return $this;
    }

    //----------------------------------------------------------------------
    public function encodeInput(Qr_Input $input)
    {
        return $this->encodeMask($input, -1);
    }
    
    //----------------------------------------------------------------------
    public function encodeString8bit($string, $version, $level)
    {
        if(string == NULL) {
            throw new Qr_Exception('empty string!');
            return NULL;
        }

        $input = new Qr_Input($version, $level);
        if($input == NULL) return NULL;

        $ret = $input->append($input, self::$QR_MODE_8, strlen($string), str_split($string));
        if($ret < 0) {
            unset($input);
            return NULL;
        }
        return $this->encodeInput($input);
    }

    //----------------------------------------------------------------------
    public function encodeString($string, $version, $level, $hint, $casesensitive)
    {
        if($hint != self::$QR_MODE_8 && $hint != self::$QR_MODE_KANJI) {
            throw new Qr_Exception('bad hint');
        }

        $input = new Qr_Input($version, $level);
        if($input == NULL) return NULL;

        $ret = Qr_Split::splitStringToQRinput($string, $input, $hint, $casesensitive);
        if($ret < 0) {
            return NULL;
        }

        return $this->encodeInput($input);
    }
    
    //----------------------------------------------------------------------
    public static function svg($text, $outfile = false, $level, $margin = 4) 
    {
        $enc = Qr_Encode::factory($level, 20, $margin);
        return $enc->encodeSVG($text, $outfile, $saveandprint=false);
    }
    
    public static function html5($text, $outfile = false, $level, $margin = 4) 
    {
        $enc = Qr_Encode::factory($level, 20, $margin);
        return $enc->encodeHTML5($text, $outfile, $saveandprint=false);
    }
}