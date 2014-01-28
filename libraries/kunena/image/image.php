<?php
/**
 * Kunena Component
 * @package     Kunena.Framework
 * @subpackage  Image
 *
 * @copyright   (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.kunena.org
 **/
defined('_JEXEC') or die;

/**
 * Helper class for image manipulation.
 */
class KunenaImageImage extends JImage
{
/**
	 * Method to resize the current image.
	 *
	 * @param   mixed    $width        The width of the resized image in pixels or a percentage.
	 * @param   mixed    $height       The height of the resized image in pixels or a percentage.
	 * @param   boolean  $createNew    If true the current image will be cloned, resized and returned; else
	 *                                 the current image will be resized and returned.
	 * @param   integer  $scaleMethod  Which method to use for scaling
	 *
	 * @return  JImage
	 *
	 * @since   11.3
	 * @throws  LogicException
	 */
	public function resize($width, $height, $createNew = true, $scaleMethod = self::SCALE_INSIDE)
	{
		// Make sure the resource handle is valid.
		if (!$this->isLoaded())
		{
			throw new LogicException('No valid image was loaded.');
		}

		// Sanitize width.
		$width = $this->sanitizeWidth($width, $height);

		// Sanitize height.
		$height = $this->sanitizeHeight($height, $width);

		// Prepare the dimensions for the resize operation.
		$dimensions = $this->prepareDimensions($width, $height, $scaleMethod);

		// Instantiate offset.
		$offset = new stdClass;
		$offset->x = $offset->y = 0;

		// Center image if needed and create the new truecolor image handle.
		if ($scaleMethod == self::SCALE_FIT)
		{
			// Get the offsets
			$offset->x	= round(($width - $dimensions->width) / 2);
			$offset->y	= round(($height - $dimensions->height) / 2);

			$handle = imagecreatetruecolor($width, $height);

			// Make image transparent, otherwise cavas outside initial image would default to black
			if (!$this->isTransparent())
			{
				$transparency = imagecolorAllocateAlpha($this->handle, 0, 0, 0, 127);
				imagecolorTransparent($this->handle, $transparency);
			}
		}
		else
		{
			$handle = imagecreatetruecolor($dimensions->width, $dimensions->height);
		}

		// Allow transparency for the new image handle.
		imagealphablending($handle, false);
		imagesavealpha($handle, true);

		if ($this->isTransparent())
		{
			// Get the transparent color values for the current image.
			$rgba = imageColorsForIndex($this->handle, imagecolortransparent($this->handle));
			$color = imageColorAllocateAlpha($this->handle, $rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha']);

			// Set the transparent color values for the new image.
			imagecolortransparent($handle, $color);
			imagefill($handle, 0, 0, $color);

			$this->imageCopyResampledBicubic($handle, $this->handle, $offset->x, $offset->y, 0, 0, $dimensions->width, $dimensions->height, $this->getWidth(), $this->getHeight());
		}
		else
		{
			$this->imageCopyResampledBicubic($handle, $this->handle, $offset->x, $offset->y, 0, 0, $dimensions->width, $dimensions->height, $this->getWidth(), $this->getHeight());
		}

		// If we are resizing to a new image, create a new JImage object.
		if ($createNew)
		{
			// @codeCoverageIgnoreStart
			$new = new JImage($handle);

			return $new;

			// @codeCoverageIgnoreEnd
		}
		// Swap out the current handle for the new image handle.
		else
		{
			// Free the memory from the current handle
			$this->destroy();

			$this->handle = $handle;

			return $this;
		}
	}

	protected function imageCopyResampledBicubic(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)  {
        // we should first cut the piece we are interested in from the source
        $src_img = ImageCreateTrueColor($src_w, $src_h);
        imagecopy($src_img, $src_image, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // this one is used as temporary image
        $dst_img = ImageCreateTrueColor($dst_w, $dst_h);

        ImagePaletteCopy($dst_img, $src_img);
        $rX = $src_w / $dst_w;
        $rY = $src_h / $dst_h;
        $w = 0;
        for ($y = 0; $y < $dst_h; $y++)  {
            $ow = $w; $w = round(($y + 1) * $rY);
            $t = 0;
            for ($x = 0; $x < $dst_w; $x++)  {
                $r = $g = $b = 0; $a = 0;
                $ot = $t; $t = round(($x + 1) * $rX);
                for ($u = 0; $u < ($w - $ow); $u++)  {
                    for ($p = 0; $p < ($t - $ot); $p++)  {
                        $c = ImageColorsForIndex($src_img, ImageColorAt($src_img, $ot + $p, $ow + $u));
                        $r += $c['red'];
                        $g += $c['green'];
                        $b += $c['blue'];
                        $a++;
                    }
                }
                ImageSetPixel($dst_img, $x, $y, ImageColorClosest($dst_img, $r / $a, $g / $a, $b / $a));
            }
        }

        // apply the temp image over the returned image and use the destination x,y coordinates
        imagecopy($dst_image, $dst_img, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h);

        // we should return true since ImageCopyResampled/ImageCopyResized do it
        return true;
    }

	
}
