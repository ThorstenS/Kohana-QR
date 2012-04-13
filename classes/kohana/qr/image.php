<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * PHP QR Code encoder
 *
 * Image output of code using GD2
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
abstract class Kohana_Qr_Image extends Qr {
    
    //----------------------------------------------------------------------
    public static function svg($frame, $subtitle = '')
    {
        return Qr_Image::imageSVG($frame, $subtitle);
    }
    
    //----------------------------------------------------------------------
    protected static function imageSVG($frame, $subtitle)
    {
        $h = count($frame);
        $w = strlen($frame[0]);
        
        $resize_factor = 10;
        $outerFrame = 4;
        $imgW = $resize_factor * $w + 2*$outerFrame;
        $imgH = $resize_factor * $h + 4*$outerFrame;
        
        // Add white background for good contrast
        $eps_string = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"><rect x="0" y="0" width="'.$imgW.'" height="'.$imgH.'" style="fill:white"/>';
        
        // Add the actual pixels
        for($y=0; $y<$h; $y++) {
            for($x=0; $x<$w; $x++) {
                if ($frame[$y][$x] == '1') {
                    $eps_string .= '<rect x="'.($x*$resize_factor+$outerFrame).'" y="'.($y*$resize_factor+$outerFrame).'" width="' . 1*$resize_factor . '" height="' . 1*$resize_factor . '" style="fill:black"/>';
                }
            }
        }
        
        if (strlen($subtitle) > 27)
        {
            $subtitle = substr($subtitle, 0, 25) . '…';
        }
        
        $eps_string .= '<text width="50" x="' . $outerFrame . '" y="' . (($y + 2)*$resize_factor+$outerFrame) . '" style="font-size:'. 2*$resize_factor .'px; width: 50px; text-overflow: hidden;">'.$subtitle.'</text></svg>';
        
        return $eps_string;
	}
}