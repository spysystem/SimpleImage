<?php
/**
 * The PHP Simple Image class - v1.0
 *  By Cory LaViska - http://abeautifulsite.net/
 *
 * License
 *  This software is dual-licensed under the GNU General Public License and
 *  the MIT License and is copyright A Beautiful Site, LLC.
 */
namespace SpyClaviska;

use League\ColorExtractor\Color;

/**
 * Class SimpleImage
 *
 * @package SpyClaviska
 */
class SimpleImage
{
	/**
	 * Loads an image into a resource variable and gets the appropriate image information
	 *
	 * @param string $src
	 *
	 * @return mixed[]|bool
	 */
	private function load(string $src)
	{
		$info = getimagesize($src);
		if(!$info)
		{
			return false;
		}

		switch($info['mime'])
		{
			case 'image/gif':
				$image = imagecreatefromgif($src);
				break;

			case 'image/jpeg':
				$image = imagecreatefromjpeg($src);
				break;

			case 'image/png':
				$image = imagecreatefrompng($src);
				break;

			default:
				// Unsupported image type
				return false;
				break;
		}

		return [$image, $info];
	}

	/**
	 * Saves an image resource to file
	 *
	 * @param resource $image
	 * @param string   $filename
	 * @param string   $type
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	private function save($image, string $filename, string $type, ?int $quality = null): bool
	{
		switch($type)
		{
			case 'image/gif':
				return imagegif($image, $filename);
				break;

			case 'image/jpeg':
				if($quality === null)
				{
					$quality = 85;
				}
				if($quality < 0)
				{
					$quality = 0;
				}
				if($quality > 100)
				{
					$quality = 100;
				}

				return imagejpeg($image, $filename, $quality);
				break;

			case 'image/png':
				if($quality === null)
				{
					$quality = 9;
				}
				if($quality > 9)
				{
					$quality = 9;
				}
				if($quality < 1)
				{
					$quality = 0;
				}

				return imagepng($image, $filename, $quality);
				break;
		}

		return false;
	}

	/**
	 * Same as PHP's imagecopymerge() function, except preserves alpha-transparency in 24-bit PNGs
	 *
	 * @param resource $dst_im
	 * @param resource $src_im
	 * @param int      $dst_x
	 * @param int      $dst_y
	 * @param int      $src_x
	 * @param int      $src_y
	 * @param int      $src_w
	 * @param int      $src_h
	 * @param int      $pct
	 *
	 * @return bool
	 */
	private function imagecopymerge_alpha($dst_im, $src_im, int $dst_x, int $dst_y, int $src_x, int $src_y, int $src_w, int $src_h, int $pct): bool
	{
		$cut = imagecreatetruecolor($src_w, $src_h);
		imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
		imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

		return imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
	}

	/**
	 * Converts a hex color value to its RGB equivalent
	 *
	 * @param string $hex_color
	 *
	 * @return array|bool
	 */
	private function hex2rgb(string $hex_color)
	{
		if($hex_color[0] == '#')
		{
			$hex_color = substr($hex_color, 1);
		}

		if(strlen($hex_color) == 6)
		{
			list($r, $g, $b) = [
				$hex_color[0].$hex_color[1],
				$hex_color[2].$hex_color[3],
				$hex_color[4].$hex_color[5]
			];
		}
		elseif(strlen($hex_color) == 3)
		{
			list($r, $g, $b) = [
				$hex_color[0].$hex_color[0],
				$hex_color[1].$hex_color[1],
				$hex_color[2].$hex_color[2]
			];
		}
		else
		{
			return false;
		}

		return [
			'r' => hexdec($r),
			'g' => hexdec($g),
			'b' => hexdec($b)
		];
	}

	/**
	 * Convert an image from one type to another; output type is determined by $dest's file extension
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function convert(string $src, string $dest, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original) = $img->load($src);

		switch(strtolower(preg_replace('/^.*\./', '', $dest)))
		{
			case 'gif':
				return $img->save($original, $dest, 'image/gif');
				break;

			case 'jpg':
			case 'jpeg':
				return $img->save($original, $dest, 'image/jpeg', $quality);
				break;

			case 'png':
				return $img->save($original, $dest, 'image/png', $quality);
				break;
		}

		return false;
	}

	/**
	 * Flip an image horizontally or vertically
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param string   $direction
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function flip(string $src, string $dest, string $direction, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		$new = imagecreatetruecolor($info[0], $info[1]);

		switch(strtolower($direction))
		{
			case 'vertical':
			case 'v':
			case 'y':
				for($y = 0; $y < $info[1]; $y++)
				{
					imagecopy($new, $original, 0, $y, 0, $info[1] - $y - 1, $info[0], 1);
				}
				break;

			case 'horizontal':
			case 'h':
			case 'x':
				for($x = 0; $x < $info[0]; $x++)
				{
					imagecopy($new, $original, $x, 0, $info[0] - $x - 1, 0, 1, $info[1]);
				}
				break;
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Rotate an image
	 *
	 * @param string     $src
	 * @param string     $dest
	 * @param int|string $angle
	 * @param string     $bg_color
	 * @param int|null   $quality
	 *
	 * @return bool
	 */
	public static function rotate(string $src, string $dest, $angle = 270, string $bg_color = '#FFFFFF', ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Determine angle
		$angle = strtolower($angle);
		if($angle === 'cw' || $angle === 'clockwise')
		{
			$angle = 270;
		}
		if($angle === 'ccw' || $angle === 'counterclockwise')
		{
			$angle = 90;
		}

		$rgb      = $img->hex2rgb($bg_color);
		$bg_color = imagecolorallocate($original, $rgb['r'], $rgb['g'], $rgb['b']);

		$new = imagerotate($original, $angle, $bg_color);

		/**
		 * Suppress the warning: tempnam(): file created in the system's temporary directory
		 * @see https://www.php.net/ChangeLog-7.php#7.1.0
		 *      Fixed bug #69489 (tempnam() should raise notice if falling back to temp dir).
		 */
		$desttmp  = @tempnam('/tmp', 'img-rotate');
		$bSuccess = false;
		if($img->save($new, $desttmp, $info['mime'], $quality))
		{
			$bSuccess = rename($desttmp, $dest);
		}

		return $bSuccess;
	}

	/**
	 * Convert an image from color to grayscale ("desaturate")
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function grayscale(string $src, string $dest, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_GRAYSCALE);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Invert image colors
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function invert(string $src, string $dest, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_NEGATE);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Adjust image brightness
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $level
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function brightness(string $src, string $dest, int $level, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_BRIGHTNESS, $level);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Adjust image contrast
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $level
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function contrast(string $src, string $dest, int $level, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_CONTRAST, $level);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Colorize an image (requires PHP 5.2.5+)
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $red
	 * @param int      $green
	 * @param int      $blue
	 * @param int      $alpha
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function colorize(string $src, string $dest, int $red, int $green, int $blue, int $alpha, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Highlight image edges
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function edgedetect(string $src, string $dest, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_EDGEDETECT);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Emboss an image
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function emboss(string $src, string $dest, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_EMBOSS);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Blur an image
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $level
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function blur(string $src, string $dest, int $level = 1, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		for($i = 0; $i < $level; $i++)
		{
			imagefilter($original, IMG_FILTER_GAUSSIAN_BLUR);
		}

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Create a sketch effect
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $level
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function sketch(string $src, string $dest, int $level = 1, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		for($i = 0; $i < $level; $i++)
		{
			imagefilter($original, IMG_FILTER_MEAN_REMOVAL);
		}

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Make image smoother
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $level
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function smooth(string $src, string $dest, int $level, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_SMOOTH, $level);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Make image pixelized (requires PHP 5.3+)
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $block_size
	 * @param bool     $advanced_pix
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function pixelate(string $src, string $dest, int $block_size, bool $advanced_pix = false, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, 11, $block_size, $advanced_pix);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Produce a sepia-like effect
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function sepia(string $src, string $dest, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		imagefilter($original, IMG_FILTER_GRAYSCALE);
		imagefilter($original, IMG_FILTER_COLORIZE, 90, 60, 30);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Resize an image to the specified dimensions
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $new_width
	 * @param int      $new_height
	 * @param bool     $resample
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function resize(string $src, string $dest, int $new_width, int $new_height, bool $resample = true, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		$new = imagecreatetruecolor($new_width, $new_height);

		// Preserve alphatransparency in PNGs
		imagealphablending($new, false);
		imagesavealpha($new, true);

		if($resample)
		{
			imagecopyresampled($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}
		else
		{
			imagecopyresized($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Proportionally scale an image to fit the specified width
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $new_width
	 * @param bool     $resample
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function resize_to_width(string $src, string $dest, int $new_width, bool $resample = true, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Determine aspect ratio
		$aspect_ratio = $info[1] / $info[0];

		// Adjust height proportionally to new width
		$new_height = $new_width * $aspect_ratio;

		$new = imagecreatetruecolor($new_width, $new_height);

		// Preserve alphatransparency in PNGs
		imagealphablending($new, false);
		imagesavealpha($new, true);

		if($resample)
		{
			imagecopyresampled($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}
		else
		{
			imagecopyresized($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Proportionally scale an image to fit the specified height
	 *
	 * @param string      $src
	 * @param string      $dest
	 * @param int         $new_height
	 * @param bool        $resample
	 * @param int|null    $quality
	 * @param string|null $new_type
	 * @param bool        $white_background
	 *
	 * @return bool
	 */
	public static function resize_to_height(string $src, string $dest, int $new_height, bool $resample = true, ?int $quality = null, ?string $new_type = null, bool $white_background = false): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Determine aspect ratio
		$aspect_ratio = $info[1] / $info[0];

		// Adjust height proportionally to new width
		$new_width = $new_height / $aspect_ratio;

		$new = imagecreatetruecolor($new_width, $new_height);

		if($white_background)
		{
			// Make the standard background for transparent images WHITE instead of BLACK (e.g. when you convert from png to jpeg).
			$white = imagecolorallocate($new, 255, 255, 255);
			imagefilledrectangle($new, 0, 0, $new_width, $new_height, $white);
		}
		else
		{
			// Preserve alphatransparency in PNGs
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}

		if($resample)
		{
			imagecopyresampled($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}
		else
		{
			imagecopyresized($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}

		if($new_type)
		{
			$info['mime'] = $new_type;
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Proportionally shrink an image to fit within a specified width/height
	 *
	 * @param string      $src
	 * @param string      $dest
	 * @param int         $max_width
	 * @param int         $max_height
	 * @param bool        $resample
	 * @param int|null    $quality
	 * @param string|null $new_type
	 * @param bool        $white_background
	 *
	 * @return bool
	 */
	public static function shrink_to_fit(string $src, string $dest, int $max_width, int $max_height, bool $resample = true, ?int $quality = null, ?string $new_type = null, bool $white_background = false): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Determine aspect ratio
		$aspect_ratio = $info[1] / $info[0];

		// Make width fit into new dimensions
		if($info[0] > $max_width)
		{
			$new_width  = $max_width;
			$new_height = $new_width * $aspect_ratio;
		}
		else
		{
			$new_width  = $info[0];
			$new_height = $info[1];
		}

		// Make height fit into new dimensions
		if($new_height > $max_height)
		{
			$new_height = $max_height;
			$new_width  = $new_height / $aspect_ratio;
		}

		$new = imagecreatetruecolor($new_width, $new_height);

		if($white_background)
		{
			// Make the standard background for transparent images WHITE instead of BLACK (e.g. when you convert from png to jpeg).
			$white = imagecolorallocate($new, 255, 255, 255);
			imagefilledrectangle($new, 0, 0, $new_width, $new_height, $white);
		}
		else
		{
			// Preserve alphatransparency in PNGs
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}

		if($resample)
		{
			imagecopyresampled($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}
		else
		{
			imagecopyresized($new, $original, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}
		if($new_type)
		{
			$info['mime'] = $new_type;
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Proportionally shrink an image to fit within a specified width/height
	 *
	 * @param string      $src
	 * @param string      $dest
	 * @param int         $size
	 * @param bool        $resample
	 * @param int|null    $quality
	 * @param string|null $new_type
	 * @param bool        $white_background
	 * @param string|null $background_color
	 *
	 * @return bool
	 */
	public static function shrink_to_square(string $src, string $dest, int $size, bool $resample = true, ?int $quality = null, ?string $new_type = null, bool $white_background = false, ?string $background_color = null): bool
	{
		if($background_color === null)
		{
			$background_color = 'FFFFFF';
		}

		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Determine aspect ratio
		$aspect_ratio = $info[1] / $info[0];

		// Make width fit into new dimensions
		if($info[0] > $size)
		{
			$new_width  = $size;
			$new_height = $new_width * $aspect_ratio;
		}
		else
		{
			$new_width  = $info[0];
			$new_height = $info[1];
		}

		// Make height fit into new dimensions
		if($new_height > $size)
		{
			$new_height = $size;
			$new_width  = $new_height / $aspect_ratio;
		}

		// Create the new image and fill it with white
		$new				= imagecreatetruecolor($size, $size);
		$rgb				= $img->hex2rgb($background_color);
		$backgroundColor	= imagecolorallocate($new, $rgb['r'], $rgb['g'], $rgb['b']);
		imagefill($new, 0, 0, $backgroundColor);

		if($white_background)
		{
			// Make the standard background for transparent images WHITE instead of BLACK (e.g. when you convert from png to jpeg).
			$white = imagecolorallocate($new, 255, 255, 255);
			imagefilledrectangle($new, 0, 0, $new_width, $new_height, $white);
		}
		else
		{
			// Preserve alphatransparency in PNGs
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}

		$arrPos = [0, 0];

		// Find the new pos for the image
		if($size > $new_height)
		{
			$arrPos[1] = ($size - $new_height) / 2;
		}
		elseif($size > $new_width)
		{
			$arrPos[0] = ($size - $new_width) / 2;
		}

		if($info[0] < $size && $info[1] < $size)
		{
			$arrPos[0] = ($size - $info[0]) / 2;
		}

		if($resample)
		{
			imagecopyresampled($new, $original, $arrPos[0], $arrPos[1], 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}
		else
		{
			imagecopyresized($new, $original, $arrPos[0], $arrPos[1], 0, 0, $new_width, $new_height, $info[0], $info[1]);
		}

		// Override mimetype (say you want to convert 'image/png' to 'image/jpeg'
		if($new_type)
		{
			$info['mime'] = $new_type;
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Shrink source image to non-white, and then shrink to square and save to destination
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $size
	 * @param bool     $resample
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function shrink_to_square_non_white(string $src, string $dest, int $size, bool $resample = true, ?int $quality = null): bool
	{
		SimpleImage::shrink_to_non_white($src);

		return SimpleImage::shrink_to_square($src, $dest, $size, $resample, $quality);
	}

	/**
	 * Get HEX color for specified position in image
	 *
	 * @param string $src
	 * @param int    $x
	 * @param int    $y
	 *
	 * @return string
	 */
	public static function get_color_at_position(string $src, int $x = 0, int $y = 0): string
	{
		$oImage = new SimpleImage();
		list($rImage) = $oImage->load($src);

		$iColor	= imagecolorat($rImage, $x, $y);

		return Color::fromIntToHex($iColor, true);
	}

	/**
	 * Shrink image to non-white, ie. remove white borders arround the actual image
	 *
	 * @param string      $src
	 * @param int|null    $quality
	 * @param string|null $background_color
	 *
	 * @return bool
	 */
	public static function shrink_to_non_white(string $src, ?int $quality = 100, ?string $background_color = null): bool
	{
		$oImage = new SimpleImage();
		list($rImage, $arrInfo) = $oImage->load($src);

		$arrBox = SimpleImage::imageTrimBox($rImage, $background_color);

		// Resize and crop
		$rNewImage = imagecreatetruecolor($arrBox['w'], $arrBox['h']);

		// Preserve alphatransparency in PNGs
		imagealphablending($rNewImage, false);
		imagesavealpha($rNewImage, true);

		imagecopyresampled($rNewImage, $rImage, 0, 0, $arrBox['l'], $arrBox['t'], $arrBox['w'], $arrBox['h'], $arrBox['w'], $arrBox['h']);

		return $oImage->save($rNewImage, $src, $arrInfo['mime'], $quality);
	}

	/**
	 * Get trim box for image, used for shrinking to non-white
	 *
	 * @param resource    $rImage
	 * @param string|null $hex
	 *
	 * @return array
	 */
	public static function imageTrimBox($rImage, ?string $hex = null): array
	{
		if(!ctype_xdigit($hex))
		{
			$hex = imagecolorat($rImage, 0, 0);
		}

		$b_top = $b_lft = 0;
		$b_rt  = $w1 = $w2 = imagesx($rImage);
		$b_btm = $h1 = $h2 = imagesy($rImage);

		do
		{
			//top
			for(; $b_top < $h1; ++$b_top)
			{
				for($x = 0; $x < $w1; ++$x)
				{
					if(imagecolorat($rImage, $x, $b_top) != $hex)
					{
						break 2;
					}
				}
			}

			// stop if all pixels are trimmed
			if($b_top == $b_btm)
			{
				$b_top = 0;
				$iCode = 2;
				break 1;
			}

			// bottom
			for(; $b_btm > 0; --$b_btm)
			{
				for($x = 0; $x < $w1; ++$x)
				{
					if(imagecolorat($rImage, $x, $b_btm - 1) != $hex)
					{
						break 2;
					}
				}
			}

			// left
			for(; $b_lft < $w1; ++$b_lft)
			{
				for($y = $b_top; $y < $b_btm; ++$y)
				{
					if(imagecolorat($rImage, $b_lft, $y) != $hex)
					{
						break 2;
					}
				}
			}

			// right
			for(; $b_rt > 0; --$b_rt)
			{
				for($y = $b_top; $y < $b_btm; ++$y)
				{
					if(imagecolorat($rImage, $b_rt - 1, $y) != $hex)
					{
						break 2;
					}
				}
			}

			$w2    = $b_rt - $b_lft;
			$h2    = $b_btm - $b_top;
			$iCode = ($w2 < $w1 || $h2 < $h1) ? 1 : 0;
		}
		while(0);

		// Result codes:
		// 0 = Trim Zero Pixels
		// 1 = Trim Some Pixels
		// 2 = Trim All Pixels
		return [
			'#'  => $iCode,   // result code
			'l'  => $b_lft,  // left
			't'  => $b_top,  // top
			'r'  => $b_rt,   // right
			'b'  => $b_btm,  // bottom
			'w'  => $w2,     // new width
			'h'  => $h2,     // new height
			'w1' => $w1,     // original width
			'h1' => $h1,     // original height
		];
	}

	/**
	 * Crop an image and optionally resize the resulting piece
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int      $x1
	 * @param int      $y1
	 * @param int      $x2
	 * @param int      $y2
	 * @param int|null $new_width
	 * @param int|null $new_height
	 * @param bool     $resample
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function crop(string $src, string $dest, int $x1, int $y1, int $x2, int $y2, ?int $new_width = null, ?int $new_height = null, bool $resample = true, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Crop size
		if($x2 < $x1)
		{
			list($x1, $x2) = [$x2, $x1];
		}
		if($y2 < $y1)
		{
			list($y1, $y2) = [$y2, $y1];
		}
		$crop_width  = $x2 - $x1;
		$crop_height = $y2 - $y1;

		if($new_width == null)
		{
			$new_width = $crop_width;
		}
		if($new_height == null)
		{
			$new_height = $crop_height;
		}

		$new = imagecreatetruecolor($new_width, $new_height);

		// Preserve alphatransparency in PNGs
		imagealphablending($new, false);
		imagesavealpha($new, true);

		// Create the new image
		if($resample)
		{
			imagecopyresampled($new, $original, 0, 0, $x1, $y1, $new_width, $new_height, $crop_width, $crop_height);
		}
		else
		{
			imagecopyresized($new, $original, 0, 0, $x1, $y1, $new_width, $new_height, $crop_width, $crop_height);
		}

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Trim the edges of a portrait or landscape image to make it square and optionally resize the resulting image
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param int|null $new_size
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function square_crop(string $src, string $dest, ?int $new_size = null, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		// Calculate measurements
		if($info[0] > $info[1])
		{
			// For landscape images
			$x_offset    = ($info[0] - $info[1]) / 2;
			$y_offset    = 0;
			$square_size = $info[0] - ($x_offset * 2);
		}
		else
		{
			// For portrait and square images
			$x_offset    = 0;
			$y_offset    = ($info[1] - $info[0]) / 2;
			$square_size = $info[1] - ($y_offset * 2);
		}

		if($new_size == null)
		{
			$new_size = $square_size;
		}

		// Resize and crop
		$new = imagecreatetruecolor($new_size, $new_size);

		// Preserve alphatransparency in PNGs
		imagealphablending($new, false);
		imagesavealpha($new, true);

		imagecopyresampled($new, $original, 0, 0, $x_offset, $y_offset, $new_size, $new_size, $square_size, $square_size);

		return $img->save($new, $dest, $info['mime'], $quality);
	}

	/**
	 * Overlay an image on top of another image with opacity; works with 24-bit PNG alpha-transparency
	 *
	 * @param string   $src
	 * @param string   $dest
	 * @param string   $watermark_src
	 * @param string   $position
	 * @param int      $opacity
	 * @param int      $margin
	 * @param int|null $quality
	 *
	 * @return bool
	 */
	public static function watermark(string $src, string $dest, string $watermark_src, string $position = 'center', int $opacity = 50, int $margin = 0, ?int $quality = null): bool
	{
		$img = new SimpleImage;
		list($original, $info) = $img->load($src);
		list($watermark, $watermark_info) = $img->load($watermark_src);

		switch(strtolower($position))
		{
			case 'top-left':
			case 'left-top':
				$x = 0 + $margin;
				$y = 0 + $margin;
				break;

			case 'top-right':
			case 'right-top':
				$x = $info[0] - $watermark_info[0] - $margin;
				$y = 0 + $margin;
				break;

			case 'top':
			case 'top-center':
			case 'center-top':
				$x = ($info[0] / 2) - ($watermark_info[0] / 2);
				$y = 0 + $margin;
				break;

			case 'bottom-left':
			case 'left-bottom':
				$x = 0 + $margin;
				$y = $info[1] - $watermark_info[1] - $margin;
				break;

			case 'bottom-right':
			case 'right-bottom':
				$x = $info[0] - $watermark_info[0] - $margin;
				$y = $info[1] - $watermark_info[1] - $margin;
				break;

			case 'bottom':
			case 'bottom-center':
			case 'center-bottom':
				$x = ($info[0] / 2) - ($watermark_info[0] / 2);
				$y = $info[1] - $watermark_info[1] - $margin;
				break;

			case 'left':
			case 'center-left':
			case 'left-center':
				$x = 0 + $margin;
				$y = ($info[1] / 2) - ($watermark_info[1] / 2);
				break;

			case 'right':
			case 'center-right':
			case 'right-center':
				$x = $info[0] - $watermark_info[0] - $margin;
				$y = ($info[1] / 2) - ($watermark_info[1] / 2);
				break;

			case 'center':
			default:
				$x = ($info[0] / 2) - ($watermark_info[0] / 2);
				$y = ($info[1] / 2) - ($watermark_info[1] / 2);
				break;
		}

		$img->imagecopymerge_alpha($original, $watermark, $x, $y, 0, 0, $watermark_info[0], $watermark_info[1], $opacity);

		return $img->save($original, $dest, $info['mime'], $quality);
	}

	/**
	 * Adds text on top of an image with optional shadow
	 *
	 * @param string      $src
	 * @param string      $dest
	 * @param string      $text
	 * @param string      $font_file
	 * @param int         $size
	 * @param string      $color
	 * @param string      $position
	 * @param int         $margin
	 * @param string|null $shadow_color
	 * @param int         $shadow_offset_x
	 * @param int         $shadow_offset_y
	 * @param int|null    $quality
	 *
	 * @return bool
	 */
	public static function text(string $src, string $dest, string $text, string $font_file, int $size = 12, string $color = '#000000', string $position = 'center', int $margin = 0, ?string $shadow_color = null, int $shadow_offset_x = 0, int $shadow_offset_y = 0, ?int $quality = null): bool
	{
		// This method could be improved to support the text angle
		$angle = 0;

		$img = new SimpleImage;
		list($original, $info) = $img->load($src);

		$rgb   = $img->hex2rgb($color);
		$color = imagecolorallocate($original, $rgb['r'], $rgb['g'], $rgb['b']);

		// Determine text size
		$box = imagettfbbox($size, $angle, $font_file, $text);

		// Horizontal
		$text_width  = abs($box[6] - $box[2]);
		$text_height = abs($box[7] - $box[3]);


		switch(strtolower($position))
		{
			case 'top-left':
			case 'left-top':
				$x = 0 + $margin;
				$y = 0 + $size + $margin;
				break;

			case 'top-right':
			case 'right-top':
				$x = $info[0] - $text_width - $margin;
				$y = 0 + $size + $margin;
				break;

			case 'top':
			case 'top-center':
			case 'center-top':
				$x = ($info[0] / 2) - ($text_width / 2);
				$y = 0 + $size + $margin;
				break;

			case 'bottom-left':
			case 'left-bottom':
				$x = 0 + $margin;
				$y = $info[1] - $text_height - $margin + $size;
				break;

			case 'bottom-right':
			case 'right-bottom':
				$x = $info[0] - $text_width - $margin;
				$y = $info[1] - $text_height - $margin + $size;
				break;

			case 'bottom':
			case 'bottom-center':
			case 'center-bottom':
				$x = ($info[0] / 2) - ($text_width / 2);
				$y = $info[1] - $text_height - $margin + $size;
				break;

			case 'left':
			case 'center-left':
			case 'left-center':
				$x = 0 + $margin;
				$y = ($info[1] / 2) - (($text_height / 2) - $size);
				break;

			case 'right';
			case 'center-right':
			case 'right-center':
				$x = $info[0] - $text_width - $margin;
				$y = ($info[1] / 2) - (($text_height / 2) - $size);
				break;

			case 'center':
			default:
				$x = ($info[0] / 2) - ($text_width / 2);
				$y = ($info[1] / 2) - (($text_height / 2) - $size);
				break;
		}

		if($shadow_color)
		{
			$rgb          = $img->hex2rgb($shadow_color);
			$shadow_color = imagecolorallocate($original, $rgb['r'], $rgb['g'], $rgb['b']);
			imagettftext($original, $size, $angle, $x + $shadow_offset_x, $y + $shadow_offset_y, $shadow_color, $font_file, $text);
		}

		imagettftext($original, $size, $angle, $x, $y, $color, $font_file, $text);

		return $img->save($original, $dest, $info['mime'], $quality);
	}
}

// Require GD library
if(!extension_loaded('gd'))
{
	/** @noinspection PhpUnhandledExceptionInspection */
	throw new Exception('Required extension GD is not loaded.');
}
