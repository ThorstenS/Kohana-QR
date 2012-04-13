<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * PHP QR Code encoder
 *
 * Toolset, handy and debug utilites.
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
abstract class Kohana_Qr_Tools extends Qr {

    public static function binarize($frame)
    {
        $len = count($frame);
        foreach ($frame as &$frameLine) {
            
            for($i=0; $i<$len; $i++) {
                $frameLine[$i] = (ord($frameLine[$i])&1)?'1':'0';
            }
        }
        
        return $frame;
    }
}