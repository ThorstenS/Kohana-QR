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
abstract class Kohana_Qr_Rawcode extends Qr {
    
    public $version;
    public $datacode = array();
    public $ecccode = array();
    public $blocks;
    public $rsblocks = array(); //of RSblock
    public $count;
    public $dataLength;
    public $eccLength;
    public $b1;
    
    //----------------------------------------------------------------------
    public function __construct(Qr_Input $input)
    {
        $spec = array(0,0,0,0,0);
        
        $this->datacode = $input->getByteStream();
        if(is_null($this->datacode)) {
            throw new Qr_Exception('null imput string');
        }

        Qr_Spec::getEccSpec($input->getVersion(), $input->getErrorCorrectionLevel(), $spec);

        $this->version = $input->getVersion();
        $this->b1 = Qr_Spec::rsBlockNum1($spec);
        $this->dataLength = Qr_Spec::rsDataLength($spec);
        $this->eccLength = Qr_Spec::rsEccLength($spec);
        $this->ecccode = array_fill(0, $this->eccLength, 0);
        $this->blocks = Qr_Spec::rsBlockNum($spec);
        
        $ret = $this->init($spec);
        if($ret < 0) {
            throw new Qr_Exception('block alloc error');
            return null;
        }

        $this->count = 0;
    }
    
    //----------------------------------------------------------------------
    public function init(array $spec)
    {
        $dl = Qr_Spec::rsDataCodes1($spec);
        $el = Qr_Spec::rsEccCodes1($spec);
        $rs = Qr_Rs::init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        

        $blockNo = 0;
        $dataPos = 0;
        $eccPos = 0;
        for($i=0; $i<Qr_Spec::rsBlockNum1($spec); $i++) {
            $ecc = array_slice($this->ecccode,$eccPos);
            $this->rsblocks[$blockNo] = new Qr_Rs_Block($dl, array_slice($this->datacode, $dataPos), $el,  $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode,0, $eccPos), $ecc);
            
            $dataPos += $dl;
            $eccPos += $el;
            $blockNo++;
        }

        if(Qr_Spec::rsBlockNum2($spec) == 0)
            return 0;

        $dl = Qr_Spec::rsDataCodes2($spec);
        $el = Qr_Spec::rsEccCodes2($spec);
        $rs = Qr_Rs::init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        
        if($rs == NULL) return -1;
        
        for($i=0; $i<Qr_Spec::rsBlockNum2($spec); $i++) {
            $ecc = array_slice($this->ecccode,$eccPos);
            $this->rsblocks[$blockNo] = new Qr_Rs_Block($dl, array_slice($this->datacode, $dataPos), $el, $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode,0, $eccPos), $ecc);
            
            $dataPos += $dl;
            $eccPos += $el;
            $blockNo++;
        }

        return 0;
    }
    
    //----------------------------------------------------------------------
    public function getCode()
    {
        $ret;

        if($this->count < $this->dataLength) {
            $row = $this->count % $this->blocks;
            $col = $this->count / $this->blocks;
            if($col >= $this->rsblocks[0]->dataLength) {
                $row += $this->b1;
            }
            $ret = $this->rsblocks[$row]->data[$col];
        } else if($this->count < $this->dataLength + $this->eccLength) {
            $row = ($this->count - $this->dataLength) % $this->blocks;
            $col = ($this->count - $this->dataLength) / $this->blocks;
            $ret = $this->rsblocks[$row]->ecc[$col];
        } else {
            return 0;
        }
        $this->count++;
        
        return $ret;
    }
}