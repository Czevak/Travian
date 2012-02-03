<?php
// +--------------------------------------------------------------------------+
// | Image_Graph aka GraPHPite                                                |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2003, 2004 Jesper Veggerby Hansen                          |
// | Email         pear.nosey@veggerby.dk                                |
// | Web           http://graphpite.sourceforge.net                           |
// | PEAR          http://pear.php.net/pepr/pepr-proposal-show.php?id=145     |
// +--------------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU Lesser General Public               |
// | License as published by the Free Software Foundation; either             |
// | version 2.1 of the License, or (at your option) any later version.       |
// |                                                                          |
// | This library is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU        |
// | Lesser General Public License for more details.                          |
// |                                                                          |
// | You should have received a copy of the GNU Lesser General Public         |
// | License along with this library; if not, write to the Free Software      |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA |
// +--------------------------------------------------------------------------+

/**
 * Image_Graph aka GraPHPite - PEAR PHP OO Graph Rendering Utility.
 * @package graphpite
 * @copyright Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License
 * @author Jesper Veggerby Hansen <pear.nosey@veggerby.dk>
 * @version $Id: Graph.php,v 1.7 2004/11/05 19:13:28 nosey Exp $
 */ 

/** 
 * Set the IMAGE_GRAPH_PATH to the direcotory of the Graph.php file
 */
define("IMAGE_GRAPH_PATH", dirname(__FILE__));

/**
 * Include file Graph/Include.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Include.php");

/**
 * Include file Graph/Element.php
 */
require_once(IMAGE_GRAPH_PATH . "/Graph/Element.php");

/**
 * The Graph Object - the 1st (or last) object.
 * This is the main object. The GraPHP class holds the canvas and performs the final
 * output by sending the http headers and making sure the elements are outputted.
 */
class Image_Graph extends Image_Graph_Element 
{

    /**
     * The GD Image resource.     
     * @var resource
     * @access private
     */
    var $_canvas;

    /**
     * An array of colors associated with the graph
     * @var array
     * @access private
     */
    var $_colors;

    /**
     * Number of degress to rotate the canvas, counter-clockwise
     * @var int
     * @access private
     */
    var $_rotation = 0;

    /**
     * Show generation time on graph
     * @var bool
     * @access private
     */
    var $_showTime = false;

    /**
     * Filename of output, if it will be saved to a file
     * @var string
     * @access private
     */
    var $_fileName = "";

    /**
     * Filename of a possible thumbnail
     * @var string
     * @access private
     */
    var $_thumbFileName = "";

    /**
     * Width of a possible thumbnail
     * @var int
     * @access private
     */
    var $_thumbWidth = 0;

    /**
     * Height of a possible thumbnail
     * @var string
     * @access private
     */
    var $_thumbHeight = 0;

    /**
     * Output the image to the browser
     * @var bool
     * @access private
     */
    var $_outputImage = true;

    /**
     * Antialiasing percentage
     * @var int
     * @access private
     */
    var $_antialias = 0;

    /**
     * Specifies whether the logo should be displayed or not
     * @var boolean
     * @access private
     */
    var $_hideLogo = false;

    /**
     * GraPHP [Constructor]
     * @param int $width The width of the graph in pixels	 
     * @param int $height The height of the graph in pixels	 
     */
    function &Image_Graph($width, $height)
    {
        parent::Image_Graph_Element();
        
        $this->_setCoords(0, 0, $width -1, $height -1);

        if (isset($GLOBALS['_Image_Graph_gd2'])) {
            $this->_canvas = ImageCreateTrueColor($width, $height);
            ImageAlphaBlending($this->_canvas(), true);
        } else {
            $this->_canvas = ImageCreate($width, $height);
        }

        if (file_exists($filename = (dirname(__FILE__)."/Graph/named_colors.txt"))) {
            $colorLines = file($filename);
            while (list ($id, $colorLine) = each($colorLines)) {
                list ($colorName, $colorRed, $colorGreen, $colorBlue) = explode("\t", trim($colorLine));
                define("IMAGE_GRAPH_" . $colorName, ($colorRed << 16) + ($colorGreen << 8) + $colorBlue);                
            }
        }
        
        define("IMAGE_GRAPH_TRANSPARENT", 0xabe123);
        $ID = ImageColorAllocate($this->_canvas, 0xab, 0xe1, 0x23);
        ImageColorTransparent($this->_canvas, $ID);

        $this->addFont($GLOBALS['_Image_Graph_font']);
        $this->addFont($GLOBALS['_Image_Graph_vertical_font']);

        ImageFilledRectangle($this->_canvas(), 0, 0, $width -1, $height -1, $this->_color(IMAGE_GRAPH_WHITE));
    }

    /**
     * Get a very precise timestamp
     * @return The number of seconds to a lot of decimals
     * @access private 
     */
    function _getMicroTime()
    {
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        return ($mtime);
    }

    /**
     * Gets the parent chain path	 
     * @return string A textual representation of the parent chain 
     * @access private
     */
    function _parentPath()
    {
        return get_class($this)." [$this->_iD] (".$this->width()." x ".$this->height().")";
    }

    /**
     * Returns the graph's canvas. 
     * @return resource A GD image representing the graph's canvas 
     * @access private
     */
    function _canvas()
    {
        return $this->_canvas;
    }

    /**
     * Hides the GraPHPite logo from the output 
     */
    function hideLogo()
    {
        $this->_hideLogo = true;
    }

    /**
     * Add a color.
     * This method adds a color to the graph. This causes the GD image to allocate the color
     * if necessary (i.e. using GD1 TrueColor images are not supported). This is not necessary
     * perhaps use {@see Image_Graph::newColor()} or the named color constants.  
     * @param Image_Graph_Color $color A representation of the color
     */
    function &addColor(& $color)
    {
        $color->_setParent($this);
        $this->_colors[] = & $color;            
        return $color;
    }

    /**
     * Create a new Image_Graph_color. 
     * This method creates and add a color to the graph. {@see Image_Graph::addColor()}.
     * Use this only if it is strictly necessary to use a {@see Image_Graph_Color} object, 
     * fx. for alpha-blending otherwise use the named colors or the 24-bit RGB color values.
     * @param int $red The red part or the whole part
     * @param int $green The green part (or nothing), or the alpha channel
     * @param int $blue The blue part (or nothing)
     * @param int $alpha The alpha channel (or nothing)
     */
    function &newColor($red, $green = false, $blue = false, $alpha = false)
    {
        if (($green !== false) and ($blue !== false) and (is_numeric($green)) and (is_numeric($blue))) {
            $color = & new Image_Graph_Color($red, $green, $blue);
        } else {
            $color = & new Image_Graph_Color($red);
            $alpha = $green;            
        }
        $this->addColor($color);            
        if ($alpha !== false) {
            $color->setAlpha($alpha);
        }
        return $color;
    }

    /**
     * Add a font. 
     * @param Font $font A representation of the font
     */
    function &addFont(& $font)
    {
        $font->_setParent($this);
        return $font;
    }

    /**
     * The width of the graph 
     * @return int Number of pixels representing the width of the graph
     */
    function width()
    {
        return ImageSX($this->_canvas());
    }

    /**
     * The height of the graph 
     * @return int Number of pixels representing the height of the graph
     */
    function height()
    {
        return ImageSY($this->_canvas());
    }

    /**
     * Rotate the final graph 
     * @param int $Rotation Number of degrees to rotate the canvas counter-clockwise
     */
    function rotate($rotation)
    {
        $this->_rotation = $rotation;
    }

    /**
     * The width of the graph
     * @see Image_Graph::width() 
     * @return int Number of pixels representing the width of the graph
     * @access private
     */
    function _graphWidth()
    {
        return $this->width();
    }

    /**
     * The height of the graph
     * @see Image_Graph::height() 
     * @return int Number of pixels representing the height of the graph
     * @access private
     */
    function _graphHeight()
    {
        return $this->height();
    }

    /**
     * Save the output as a file
     * @param string $fileName The filename and path of the file to save output in
     * @param bool $outputImage Output the image to the browser as well
     */
    function saveAs($fileName, $outputImage = false)
    {
        $this->_fileName = $fileName;
        $this->_outputImage = $outputImage;
    }

    /**
     * Create the output as a thumbnail
     * @param int $width The width of the thumbnail
     * @param int $height The height of the thumbnail
     * @param string $fileName The filename and path of the file to save the thumbnail in, if specified the thumbnail will be saved and the output will be the normal graph
     */
    function thumbnail($width = 80, $height = 60, $fileName = "")
    {
        $this->_thumbFileName = $fileName;
        $this->_thumbWidth = $width;
        $this->_thumbHeight = $height;
    }

    /**
     * Antialias the a single pixel in the graph
     * @param int $x1 X-coordinate of the first pixel to antialias
     * @param int $y1 Y-coordinate of the first pixel to antialias
     * @param int $x2 X-coordinate of the second pixel to antialias
     * @param int $y2 Y-coordinate of the second pixel to antialias
     * @access private
     */
    function _antialiasPixel($x1, $y1, $x2, $y2)
    {
        $rgb = ImageColorAt($this->_canvas(), $x1, $y1);
        $r1 = ($rgb >> 16) & 0xFF;
        $g1 = ($rgb >> 8) & 0xFF;
        $b1 = $rgb & 0xFF;

        $rgb = ImageColorAt($this->_canvas(), $x2, $y2);
        $r2 = ($rgb >> 16) & 0xFF;
        $g2 = ($rgb >> 8) & 0xFF;
        $b2 = $rgb & 0xFF;

        if (($r1 <> $r2) or ($g1 <> $g2) or ($b1 <> $b2)) {
            $r = round($r1 + ($r2 - $r1) * 50 / ($this->_antialias + 50));
            $g = round($g1 + ($g2 - $g1) * 50 / ($this->_antialias + 50));
            $b = round($b1 + ($b2 - $b1) * 50 / ($this->_antialias + 50));

            $rgb = ImageColorAllocate($this->_canvas(), $r, $g, $b);
            ImageSetPixel($this->_canvas(), $x2, $y2, $rgb);
        }
    }

    /**
     * Perform the antialias on the graph
     * @param int $percetage The percentage "to" antialias
     * @access private
     */
    function _performAntialias()
    {
        for ($l = 0; $l < $this->height(); $l ++) {
            for ($p = 0; $p < $this->width(); $p ++) {
                // fix pixel to the left
                if ($p > 0) {
                    $this->_antialiasPixel($p, $l, $p -1, $l);
                }

                // fix pixel to the right
                if ($p < $this->width() - 1) {
                    $this->_antialiasPixel($p, $l, $p +1, $l);
                }

                // fix pixel above
                if ($l > 0) {
                    $this->_antialiasPixel($p, $l, $p, $l -1);
                }

                // fix pixel below
                if ($l < $this->height() - 1) {
                    $this->_antialiasPixel($p, $l, $p, $l +1);
                }
            }
        }
    }

    /**
     * Antialias on the graph
     * @param int $percent The percentage "to" antialias
     */
    function antialias($percent = 5)
    {
        $this->_antialias = $percent;
    }

    /**
     * Output to the canvas
     * @param int $type The type of image to output, i.e. IMG_PNG (default) and IMG_JPEG
     */
    function done($type = IMG_PNG)
    {
        $this->_done($type);
    }
    
    /**
     * Output to the canvas
     * @param int $type The type of image to output, i.e. IMG_PNG (default) and IMG_JPEG
     * @access private
     */
    function _done($type = IMG_PNG)
    {
        $timeStart = $this->_getMicroTime();
        $this->_debug("Output started $timeStart");
        
        if ($this->_shadow) {
            $this->setPadding(20);
            $this->_setCoords($this->_left, $this->_top, $this->_right -10, $this->_bottom-10);
        }

        $this->_updateCoords();
        

        if ($this->_fillStyle) {
            ImageFilledRectangle($this->_canvas(), $this->_left, $this->_top, $this->_right, $this->_bottom, $this->_getFillStyle());
        }

        if (!file_exists(dirname(__FILE__)."/Graph/Images/logo.png")) {
            $error = "Could not find Logo your installation may be incomplete";
            ImageLine($this->_canvas(), 0, 0, $this->width(), $this->height(), $this->_color(IMAGE_GRAPH_RED));
            ImageLine($this->_canvas(), $this->width(), 0, 0, $this->height(), $this->_color(IMAGE_GRAPH_RED));
            ImageString($this->_canvas(), IMAGE_GRAPH_FONT, ($this->width() - ImageFontWidth(IMAGE_GRAPH_FONT) * strlen($error)) / 2, ($this->height() - ImageFontHeight(IMAGE_GRAPH_FONT)) / 2, $error, $this->_color(IMAGE_GRAPH_RED));
        } else {
            parent::_done();
        }
        
        if (isset($this->_borderStyle)) {
            $this->_debug("Drawing border");
            ImageRectangle($this->_canvas(), $this->_left, $this->_top, $this->_right, $this->_bottom, $this->_getBorderStyle());
        }

        if (($this->_outputImage) and (!IMAGE_GRAPH_DEBUG)) {
            header("Expires: Tue, 2 Jul 1974 17:41:00 GMT"); // Date in the past
            header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); // always modified
            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Pragma: no-cache");
            header("Content-type: image/". ($type == IMG_JPG ? "jpeg" : "png"));            
            header("Content-Disposition: attachment; filename = \"". (isset($_GET['thumb']) ? $_GET['thumb'] : (isset($_GET['image']) ? $_GET['image'] : ""))."\"");
        }

        if ($this->_rotation) {
            $this->_canvas = ImageRotate($this->_canvas(), $this->_rotation, $this->_getFillStyle());
        }

        $timeEnd = $this->_getMicroTime();
        $this->_debug("Output ended $timeEnd");

        if ($this->_showTime) {
            ImageString($this->_canvas(), FONT, $this->_left + $this->width() * 0.15, $this->_bottom - $this->_height * 0.1 - ImageFontHeight(IMAGE_GRAPH_FONT), "Generated in ".sprintf("%0.3f", $timeEnd - $timeStart)." sec", $this->_color(IMAGE_GRAPH_RED));
        }

        if (!$this->_hideLogo) {
            $logo = new Image_Graph_Logo(dirname(__FILE__)."/Graph/Images/logo.png", IMAGE_GRAPH_ALIGN_TOP_RIGHT);
            $logo->_setParent($this);
            $logo->_done();
        }

        if ($this->_antialias) {
            $this->_performAntialias();
        }

        if ($this->_fileName) {
            if (strtolower(substr($this->_fileName, -4)) == ".png") {
                ImagePNG($this->_canvas(), $this->_fileName);
            } else {
                ImageJPEG($this->_canvas(), $this->_fileName);
            }
        }

        if (($this->_thumbWidth) and ($this->_thumbHeight)) {
            if (isset($GLOBALS['_Image_Graph_gd2'])) {
                $thumbnail = ImageCreateTrueColor($this->_thumbWidth, $this->_thumbHeight);
                ImageCopyResampled($thumbnail, $this->_canvas(), 0, 0, 0, 0, $this->_thumbWidth, $this->_thumbHeight, $this->width(), $this->height());
            } else {
                $thumbnail = ImageCreate($this->_thumbWidth, $this->_thumbHeight);
                ImageCopyResized($thumbnail, $this->_canvas(), 0, 0, 0, 0, $this->_thumbWidth, $this->_thumbHeight, $this->width(), $this->height());
            }

            if ($this->_thumbFileName) {
                if (strtolower(substr($this->_thumbFileName, -4)) == ".png") {
                    ImagePNG($thumbnail, $this->_thumbFileName);
                } else {
                    ImageJPEG($thumbnail, $this->_thumbFileName);
                }
                ImageDestroy($thumbnail);
            } else {
                ImageDestroy($this->_canvas());
                $this->_canvas = $thumbnail;
            }
        }

        if (($this->_outputImage) and (!IMAGE_GRAPH_DEBUG)) {
            if ($type == IMG_JPG) {
                ImageJPEG($this->_canvas());
            } else {
                ImagePNG($this->_canvas());
            }
        }

        ImageDestroy($this->_canvas());
        $this->_debug("Completely done", true);
    }
}

?>