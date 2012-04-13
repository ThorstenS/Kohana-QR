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
abstract class Kohana_Qr_Input_Item extends Qr {
    
    public $mode;
    public $size;
    public $data;
    public $bstream;

    public function __construct($mode, $size, $data, $bstream = null) 
    {
        $setData = array_slice($data, 0, $size);
        
        if (count($setData) < $size) {
            $setData = array_merge($setData, array_fill(0,$size-count($setData),0));
        }
    
        if(!Qr_Input::check($mode, $size, $setData)) {
            throw new Qr_Exception('Error m:'.$mode.',s:'.$size.',d:'.join(',',$setData));
            return null;
        }
        
        $this->mode = $mode;
        $this->size = $size;
        $this->data = $setData;
        $this->bstream = $bstream;
    }
    
    //----------------------------------------------------------------------
    public function encodeModeNum($version)
    {
        try {
        
            $words = (int)($this->size / 3);
            $bs = new Qr_Bitstream();
            
            $val = 0x1;
            $bs->appendNum(4, $val);
            $bs->appendNum(Qr_Spec::lengthIndicator(self::$QR_MODE_NUM, $version), $this->size);

            for($i=0; $i<$words; $i++) {
                $val  = (ord($this->data[$i*3  ]) - ord('0')) * 100;
                $val += (ord($this->data[$i*3+1]) - ord('0')) * 10;
                $val += (ord($this->data[$i*3+2]) - ord('0'));
                $bs->appendNum(10, $val);
            }

            if($this->size - $words * 3 == 1) {
                $val = ord($this->data[$words*3]) - ord('0');
                $bs->appendNum(4, $val);
            } else if($this->size - $words * 3 == 2) {
                $val  = (ord($this->data[$words*3  ]) - ord('0')) * 10;
                $val += (ord($this->data[$words*3+1]) - ord('0'));
                $bs->appendNum(7, $val);
            }

            $this->bstream = $bs;
            return 0;
            
        } catch (Exception $e) {
            return -1;
        }
    }
    
    //----------------------------------------------------------------------
    public function encodeModeAn($version)
    {
        try {
            $words = (int)($this->size / 2);
            $bs = new Qr_Bitstream();
            
            $bs->appendNum(4, 0x02);
            $bs->appendNum(Qr_Spec::lengthIndicator(self::$QR_MODE_AN, $version), $this->size);

            for($i=0; $i<$words; $i++) {
                $val  = (int)Qr_Input::lookAnTable(ord($this->data[$i*2  ])) * 45;
                $val += (int)Qr_Input::lookAnTable(ord($this->data[$i*2+1]));

                $bs->appendNum(11, $val);
            }

            if($this->size & 1) {
                $val = Qr_Input::lookAnTable(ord($this->data[$words * 2]));
                $bs->appendNum(6, $val);
            }
    
            $this->bstream = $bs;
            return 0;
        
        } catch (Exception $e) {
            return -1;
        }
    }
    
    //----------------------------------------------------------------------
    public function encodeMode8($version)
    {
        try {
            $bs = new Qr_Bitstream();

            $bs->appendNum(4, 0x4);
            $bs->appendNum(Qr_Spec::lengthIndicator(self::$QR_MODE_8, $version), $this->size);

            for($i=0; $i<$this->size; $i++) {
                $bs->appendNum(8, ord($this->data[$i]));
            }

            $this->bstream = $bs;
            return 0;
        
        } catch (Exception $e) {
            return -1;
        }
    }
    
    //----------------------------------------------------------------------
    public function encodeModeStructure()
    {
        try {
            $bs =  new Qr_Bitstream();
            
            $bs->appendNum(4, 0x03);
            $bs->appendNum(4, ord($this->data[1]) - 1);
            $bs->appendNum(4, ord($this->data[0]) - 1);
            $bs->appendNum(8, ord($this->data[2]));

            $this->bstream = $bs;
            return 0;
        
        } catch (Exception $e) {
            return -1;
        }
    }
    
    //----------------------------------------------------------------------
    public function estimateBitStreamSizeOfEntry($version)
    {
        $bits = 0;

        if($version == 0) 
            $version = 1;

        switch($this->mode) {
            case self::$QR_MODE_NUM:          $bits = Qr_Input::estimateBitsModeNum($this->size);    break;
            case self::$QR_MODE_AN:           $bits = Qr_Input::estimateBitsModeAn($this->size);    break;
            case self::$QR_MODE_8:            $bits = Qr_Input::estimateBitsMode8($this->size);    break;
            case self::$QR_MODE_KANJI:        $bits = Qr_Input::estimateBitsModeKanji($this->size);break;
            case self::$QR_MODE_STRUCTURE:    return self::$STRUCTURE_HEADER_BITS;            
            default:
                return 0;
        }

        $l = Qr_Spec::lengthIndicator($this->mode, $version);
        $m = 1 << $l;
        $num = (int)(($this->size + $m - 1) / $m);

        $bits += $num * (4 + $l);

        return $bits;
    }
    
    //----------------------------------------------------------------------
    public function encodeBitStream($version)
    {
        try {
        
            unset($this->bstream);
            $words = Qr_Spec::maximumWords($this->mode, $version);
            
            if($this->size > $words) {
            
                $st1 = new Qr_Input_Item($this->mode, $words, $this->data);
                $st2 = new Qr_Input_Item($this->mode, $this->size - $words, array_slice($this->data, $words));

                $st1->encodeBitStream($version);
                $st2->encodeBitStream($version);
                
                $this->bstream = new Qr_Bitstream();
                $this->bstream->append($st1->bstream);
                $this->bstream->append($st2->bstream);
                
                unset($st1);
                unset($st2);
                
            } else {
                
                $ret = 0;
                
                switch($this->mode) {
                    case self::$QR_MODE_NUM:          $ret = $this->encodeModeNum($version);    break;
                    case self::$QR_MODE_AN:           $ret = $this->encodeModeAn($version);    break;
                    case self::$QR_MODE_8:            $ret = $this->encodeMode8($version);    break;
                    case self::$QR_MODE_KANJI:        $ret = $this->encodeModeKanji($version);break;
                    case self::$QR_MODE_STRUCTURE:    $ret = $this->encodeModeStructure();    break;
                    
                    default:
                        break;
                }
                
                if($ret < 0)
                    return -1;
            }

            return $this->bstream->size();
        
        } catch (Exception $e) {
            return -1;
        }
    }
}