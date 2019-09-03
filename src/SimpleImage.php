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
	 * @param string $strSource
	 *
	 * @return mixed[]|bool
	 */
	private function load(string $strSource)
	{
		$arrInfo	= getimagesize($strSource);
		if(!$arrInfo)
		{
			return false;
		}

		switch($arrInfo['mime'])
		{
			case 'image/gif':
				$rImage	= imagecreatefromgif($strSource);
				break;

			case 'image/jpeg':
				$rImage	= imagecreatefromjpeg($strSource);
				break;

			case 'image/png':
				$rImage	= imagecreatefrompng($strSource);
				break;

			default:
				// Unsupported image type
				return false;
				break;
		}

		return [$rImage, $arrInfo];
	}

	/**
	 * Saves an image resource to file
	 *
	 * @param resource $rImage
	 * @param string   $strFilePath
	 * @param string   $strType
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	private function save($rImage, string $strFilePath, string $strType, ?int $iQuality = null): bool
	{
		switch($strType)
		{
			case 'image/gif':
				return imagegif($rImage, $strFilePath);
				break;

			case 'image/jpeg':
				if($iQuality === null)
				{
					$iQuality	= 85;
				}
				if($iQuality < 0)
				{
					$iQuality	= 0;
				}
				if($iQuality > 100)
				{
					$iQuality	= 100;
				}

				return imagejpeg($rImage, $strFilePath, $iQuality);
				break;

			case 'image/png':
				if($iQuality === null)
				{
					$iQuality	= 9;
				}
				if($iQuality > 9)
				{
					$iQuality	= 9;
				}
				if($iQuality < 1)
				{
					$iQuality	= 0;
				}

				return imagepng($rImage, $strFilePath, $iQuality);
				break;
		}

		return false;
	}

	/**
	 * Same as PHP's imagecopymerge() function, except preserves alpha-transparency in 24-bit PNGs
	 *
	 * @param resource $rDestination
	 * @param resource $rSource
	 * @param int      $iDesinationX
	 * @param int      $iDestinationY
	 * @param int      $iSourceX
	 * @param int      $iSourceY
	 * @param int      $iSourceWidth
	 * @param int      $iSourceHeight
	 * @param int      $iPct
	 *
	 * @return bool
	 */
	private function imagecopymerge_alpha(
		$rDestination,
		$rSource,
		int	$iDesinationX,
		int	$iDestinationY,
		int	$iSourceX,
		int	$iSourceY,
		int	$iSourceWidth,
		int	$iSourceHeight,
		int	$iPct
	): bool
	{
		$rCut	= imagecreatetruecolor($iSourceWidth, $iSourceHeight);
		imagecopy($rCut, $rDestination, 0, 0, $iDesinationX, $iDestinationY, $iSourceWidth, $iSourceHeight);
		imagecopy($rCut, $rSource, 0, 0, $iSourceX, $iSourceY, $iSourceWidth, $iSourceHeight);

		return imagecopymerge($rDestination, $rCut, $iDesinationX, $iDestinationY, $iSourceX, $iSourceY, $iSourceWidth, $iSourceHeight, $iPct);
	}

	/**
	 * Converts a hex color value to its RGB equivalent
	 *
	 * @param string $strHexColor
	 *
	 * @return array|bool
	 */
	private function hex2rgb(string $strHexColor)
	{
		if($strHexColor[0] === '#')
		{
			$strHexColor	= substr($strHexColor, 1);
		}

		if(strlen($strHexColor) === 6)
		{
			[$strRed, $strGreen, $strBlue]	= [
				$strHexColor[0].$strHexColor[1],
				$strHexColor[2].$strHexColor[3],
				$strHexColor[4].$strHexColor[5]
			];
		}
		elseif(strlen($strHexColor) === 3)
		{
			[$strRed, $strGreen, $strBlue]	= [
				$strHexColor[0].$strHexColor[0],
				$strHexColor[1].$strHexColor[1],
				$strHexColor[2].$strHexColor[2]
			];
		}
		else
		{
			return false;
		}

		return [
			'r'	=> hexdec($strRed),
			'g'	=> hexdec($strGreen),
			'b'	=> hexdec($strBlue)
		];
	}

	/**
	 * Convert an image from one type to another; output type is determined by destination file extension
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function convert(string $strSource, string $strDestination, ?int $iQuality = null): bool
	{
		$oImage			= new SimpleImage;
		[$rOriginal]	= $oImage->load($strSource);

		switch(strtolower(preg_replace('/^.*\./', '', $strDestination)))
		{
			case 'gif':
				return $oImage->save($rOriginal, $strDestination, 'image/gif');
				break;

			case 'jpg':
			case 'jpeg':
				return $oImage->save($rOriginal, $strDestination, 'image/jpeg', $iQuality);
				break;

			case 'png':
				return $oImage->save($rOriginal, $strDestination, 'image/png', $iQuality);
				break;
		}

		return false;
	}

	/**
	 * Flip an image horizontally or vertically
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param string   $strDirection
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function flip(
		string	$strSource,
		string	$strDestination,
		string	$strDirection,
		?int	$iQuality	= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		$rNew	= imagecreatetruecolor($arrInfo[0], $arrInfo[1]);

		switch(strtolower($strDirection))
		{
			case 'vertical':
			case 'v':
			case 'y':
				for($iY = 0; $iY < $arrInfo[1]; $iY++)
				{
					imagecopy($rNew, $rOriginal, 0, $iY, 0, $arrInfo[1] - $iY - 1, $arrInfo[0], 1);
				}
				break;

			case 'horizontal':
			case 'h':
			case 'x':
				for($iX = 0; $iX < $arrInfo[0]; $iX++)
				{
					imagecopy($rNew, $rOriginal, $iX, 0, $arrInfo[0] - $iX - 1, 0, 1, $arrInfo[1]);
				}
				break;
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Rotate an image
	 *
	 * @param string     $strSource
	 * @param string     $strDestination
	 * @param int|string $mAngle
	 * @param string     $strBackgroundColor
	 * @param int|null   $iQuality
	 *
	 * @return bool
	 */
	public static function rotate(
		string	$strSource,
		string	$strDestination,
				$mAngle				= 270,
		string	$strBackgroundColor	= '#FFFFFF',
		?int	$iQuality			= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Determine angle
		$mAngle	= strtolower($mAngle);
		if($mAngle === 'cw' || $mAngle === 'clockwise')
		{
			$mAngle	= 270;
		}
		if($mAngle === 'ccw' || $mAngle === 'counterclockwise')
		{
			$mAngle	= 90;
		}

		$arrRgb				= $oImage->hex2rgb($strBackgroundColor);
		$iBackgroundColor	= imagecolorallocate($rOriginal, $arrRgb['r'], $arrRgb['g'], $arrRgb['b']);
		$rNew				= imagerotate($rOriginal, $mAngle, $iBackgroundColor);

		/**
		 * Suppress the warning: tempnam(): file created in the system's temporary directory
		 * @see https://www.php.net/ChangeLog-7.php#7.1.0
		 *      Fixed bug #69489 (tempnam() should raise notice if falling back to temp dir).
		 */
		$strDestinationTmp	= @tempnam('/tmp', 'img-rotate');
		$bSuccess			= false;
		if($oImage->save($rNew, $strDestinationTmp, $arrInfo['mime'], $iQuality))
		{
			$bSuccess	= rename($strDestinationTmp, $strDestination);
		}

		return $bSuccess;
	}

	/**
	 * Convert an image from color to grayscale ("desaturate")
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function grayscale(string $strSource, string $strDestination, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_GRAYSCALE);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Invert image colors
	 *
	 * @param string   $strSouce
	 * @param string   $strDestination
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function invert(string $strSouce, string $strDestination, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSouce);

		imagefilter($rOriginal, IMG_FILTER_NEGATE);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Adjust image brightness
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iLevel
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function brightness(string $strSource, string $strDestination, int $iLevel, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_BRIGHTNESS, $iLevel);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Adjust image contrast
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iLevel
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function contrast(string $strSource, string $strDestination, int $iLevel, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_CONTRAST, $iLevel);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Colorize an image (requires PHP 5.2.5+)
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iRed
	 * @param int      $iGreen
	 * @param int      $iBlue
	 * @param int      $iAlpha
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function colorize(
		string	$strSource,
		string	$strDestination,
		int		$iRed,
		int		$iGreen,
		int		$iBlue,
		int		$iAlpha,
		?int	$iQuality	= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_COLORIZE, $iRed, $iGreen, $iBlue, $iAlpha);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Highlight image edges
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function edgedetect(string $strSource, string $strDestination, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_EDGEDETECT);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Emboss an image
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function emboss(string $strSource, string $strDestination, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_EMBOSS);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Blur an image
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iLevel
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function blur(string $strSource, string $strDestination, int $iLevel = 1, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		for($iCount = 0; $iCount < $iLevel; $iCount++)
		{
			imagefilter($rOriginal, IMG_FILTER_GAUSSIAN_BLUR);
		}

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Create a sketch effect
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iLevel
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function sketch(string $strSource, string $strDestination, int $iLevel = 1, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		for($iCount = 0; $iCount < $iLevel; $iCount++)
		{
			imagefilter($rOriginal, IMG_FILTER_MEAN_REMOVAL);
		}

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Make image smoother
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iLevel
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function smooth(string $strSource, string $strDestination, int $iLevel, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_SMOOTH, $iLevel);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Make image pixelized (requires PHP 5.3+)
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iBlockSize
	 * @param bool     $bAdvancedPix
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function pixelate(
		string	$strSource,
		string	$strDestination,
		int		$iBlockSize,
		bool	$bAdvancedPix	= false,
		?int	$iQuality		= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_PIXELATE, $iBlockSize, $bAdvancedPix);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Produce a sepia-like effect
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function sepia(string $strSource, string $strDestination, ?int $iQuality = null): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		imagefilter($rOriginal, IMG_FILTER_GRAYSCALE);
		imagefilter($rOriginal, IMG_FILTER_COLORIZE, 90, 60, 30);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Resize an image to the specified dimensions
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iNewWidth
	 * @param int      $iNewHeight
	 * @param bool     $bResample
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function resize(
		string	$strSource,
		string	$strDestination,
		int		$iNewWidth,
		int		$iNewHeight,
		bool	$bResample	= true,
		?int	$iQuality	= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);
		$rNew					= imagecreatetruecolor($iNewWidth, $iNewHeight);

		// Preserve alphatransparency in PNGs
		imagealphablending($rNew, false);
		imagesavealpha($rNew, true);
		if($bResample)
		{
			imagecopyresampled($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}
		else
		{
			imagecopyresized($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Proportionally scale an image to fit the specified width
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iNewWidth
	 * @param bool     $bResample
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function resize_to_width(
		string	$strSource,
		string	$strDestination,
		int		$iNewWidth,
		bool	$bResample	= true,
		?int	$iQuality	= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Determine aspect ratio
		$fAspectRatio	= $arrInfo[1] / $arrInfo[0];

		// Adjust height proportionally to new width
		$iNewHeight	= $iNewWidth * $fAspectRatio;

		$rNew	= imagecreatetruecolor($iNewWidth, $iNewHeight);

		// Preserve alphatransparency in PNGs
		imagealphablending($rNew, false);
		imagesavealpha($rNew, true);

		if($bResample)
		{
			imagecopyresampled($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}
		else
		{
			imagecopyresized($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Proportionally scale an image to fit the specified height
	 *
	 * @param string      $strSource
	 * @param string      $strDestination
	 * @param int         $iNewHeight
	 * @param bool        $bResample
	 * @param int|null    $iQuality
	 * @param string|null $strNewType
	 * @param bool        $bWhiteBackground
	 *
	 * @return bool
	 */
	public static function resize_to_height(
		string	$strSource,
		string	$strDestination,
		int		$iNewHeight,
		bool	$bResample			= true,
		?int	$iQuality			= null,
		?string	$strNewType			= null,
		bool	$bWhiteBackground	= false
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Determine aspect ratio
		$fAspectRatio	= $arrInfo[1] / $arrInfo[0];

		// Adjust height proportionally to new width
		$iNewWidth	= $iNewHeight / $fAspectRatio;

		$rNew	= imagecreatetruecolor($iNewWidth, $iNewHeight);

		if($bWhiteBackground)
		{
			// Make the standard background for transparent images WHITE instead of BLACK (e.g. when you convert from png to jpeg).
			$iWhite	= imagecolorallocate($rNew, 255, 255, 255);
			imagefilledrectangle($rNew, 0, 0, $iNewWidth, $iNewHeight, $iWhite);
		}
		else
		{
			// Preserve alphatransparency in PNGs
			imagealphablending($rNew, false);
			imagesavealpha($rNew, true);
		}

		if($bResample)
		{
			imagecopyresampled($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}
		else
		{
			imagecopyresized($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}

		if($strNewType)
		{
			$arrInfo['mime']	= $strNewType;
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Proportionally shrink an image to fit within a specified width/height
	 *
	 * @param string      $strSource
	 * @param string      $strDestination
	 * @param int         $iMaxWidth
	 * @param int         $iMaxHeight
	 * @param bool        $bResample
	 * @param int|null    $iQuality
	 * @param string|null $strNewType
	 * @param bool        $bWhiteBackground
	 *
	 * @return bool
	 */
	public static function shrink_to_fit(
		string	$strSource,
		string	$strDestination,
		int		$iMaxWidth,
		int		$iMaxHeight,
		bool	$bResample			= true,
		?int	$iQuality			= null,
		?string	$strNewType			= null,
		bool	$bWhiteBackground	= false
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Determine aspect ratio
		$fAspectRatio	= $arrInfo[1] / $arrInfo[0];

		// Make width fit into new dimensions
		if($arrInfo[0] > $iMaxWidth)
		{
			$iNewWidth	= $iMaxWidth;
			$iNewHeight	= $iNewWidth * $fAspectRatio;
		}
		else
		{
			$iNewWidth	= $arrInfo[0];
			$iNewHeight	= $arrInfo[1];
		}

		// Make height fit into new dimensions
		if($iNewHeight > $iMaxHeight)
		{
			$iNewHeight	= $iMaxHeight;
			$iNewWidth	= $iNewHeight / $fAspectRatio;
		}

		$rNew	= imagecreatetruecolor($iNewWidth, $iNewHeight);

		if($bWhiteBackground)
		{
			// Make the standard background for transparent images WHITE instead of BLACK (e.g. when you convert from png to jpeg).
			$iWhite	= imagecolorallocate($rNew, 255, 255, 255);
			imagefilledrectangle($rNew, 0, 0, $iNewWidth, $iNewHeight, $iWhite);
		}
		else
		{
			// Preserve alphatransparency in PNGs
			imagealphablending($rNew, false);
			imagesavealpha($rNew, true);
		}

		if($bResample)
		{
			imagecopyresampled($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}
		else
		{
			imagecopyresized($rNew, $rOriginal, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}
		if($strNewType)
		{
			$arrInfo['mime']	= $strNewType;
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Proportionally shrink an image to fit within a specified width/height
	 *
	 * @param string      $strSource
	 * @param string      $strDestination
	 * @param int         $iSize
	 * @param bool        $bResample
	 * @param int|null    $iQuality
	 * @param string|null $strNewType
	 * @param bool        $bWhiteBackground
	 * @param string|null $strBackgroundColor
	 *
	 * @return bool
	 */
	public static function shrink_to_square(
		string	$strSource,
		string	$strDestination,
		int		$iSize,
		bool	$bResample			= true,
		?int	$iQuality			= null,
		?string	$strNewType			= null,
		bool	$bWhiteBackground	= false,
		?string	$strBackgroundColor	= null
	): bool
	{
		if($strBackgroundColor === null)
		{
			$strBackgroundColor	= 'FFFFFF';
		}

		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Determine aspect ratio
		$fAspectRatio	= $arrInfo[1] / $arrInfo[0];

		// Make width fit into new dimensions
		if($arrInfo[0] > $iSize)
		{
			$iNewWidth	= $iSize;
			$iNewHeight	= $iNewWidth * $fAspectRatio;
		}
		else
		{
			$iNewWidth	= $arrInfo[0];
			$iNewHeight	= $arrInfo[1];
		}

		// Make height fit into new dimensions
		if($iNewHeight > $iSize)
		{
			$iNewHeight	= $iSize;
			$iNewWidth	= $iNewHeight / $fAspectRatio;
		}

		// Create the new image and fill it with white
		$rNew				= imagecreatetruecolor($iSize, $iSize);
		$arrRgb				= $oImage->hex2rgb($strBackgroundColor);
		$iBackgroundColor	= imagecolorallocate($rNew, $arrRgb['r'], $arrRgb['g'], $arrRgb['b']);
		imagefill($rNew, 0, 0, $iBackgroundColor);

		if($bWhiteBackground)
		{
			// Make the standard background for transparent images WHITE instead of BLACK (e.g. when you convert from png to jpeg).
			$iWhite	= imagecolorallocate($rNew, 255, 255, 255);
			imagefilledrectangle($rNew, 0, 0, $iNewWidth, $iNewHeight, $iWhite);
		}
		else
		{
			// Preserve alphatransparency in PNGs
			imagealphablending($rNew, false);
			imagesavealpha($rNew, true);
		}

		$arrPos	= [0, 0];

		// Find the new pos for the image
		if($iSize > $iNewHeight)
		{
			$arrPos[1]	= ($iSize - $iNewHeight) / 2;
		}
		elseif($iSize > $iNewWidth)
		{
			$arrPos[0]	= ($iSize - $iNewWidth) / 2;
		}

		if($arrInfo[0] < $iSize && $arrInfo[1] < $iSize)
		{
			$arrPos[0]	= ($iSize - $arrInfo[0]) / 2;
		}

		if($bResample)
		{
			imagecopyresampled($rNew, $rOriginal, $arrPos[0], $arrPos[1], 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}
		else
		{
			imagecopyresized($rNew, $rOriginal, $arrPos[0], $arrPos[1], 0, 0, $iNewWidth, $iNewHeight, $arrInfo[0], $arrInfo[1]);
		}

		// Override mimetype (say you want to convert 'image/png' to 'image/jpeg'
		if($strNewType)
		{
			$arrInfo['mime']	= $strNewType;
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Shrink source image to non-white, and then shrink to square and save to destination
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iSize
	 * @param bool     $bResample
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function shrink_to_square_non_white(
		string	$strSource,
		string	$strDestination,
		int		$iSize,
		bool	$bResample	= true,
		?int	$iQuality	= null
	): bool
	{
		SimpleImage::shrink_to_non_white($strSource);

		return SimpleImage::shrink_to_square($strSource, $strDestination, $iSize, $bResample, $iQuality);
	}

	/**
	 * Get HEX color for specified position in image
	 *
	 * @param string $strSource
	 * @param int    $iX
	 * @param int    $iY
	 *
	 * @return string
	 */
	public static function get_color_at_position(string $strSource, int $iX = 0, int $iY = 0): string
	{
		$oImage		= new SimpleImage();
		[$rImage]	= $oImage->load($strSource);

		$iColor	= imagecolorat($rImage, $iX, $iY);

		return Color::fromIntToHex($iColor, true);
	}

	/**
	 * Shrink image to non-white, ie. remove white borders arround the actual image
	 *
	 * @param string      $strSource
	 * @param int|null    $iQuality
	 * @param string|null $strBackgroundColor
	 *
	 * @return bool
	 */
	public static function shrink_to_non_white(
		string	$strSource,
		?int	$iQuality			= 100,
		?string	$strBackgroundColor	= null
	): bool
	{
		$oImage				= new SimpleImage();
		[$rImage, $arrInfo]	= $oImage->load($strSource);

		$arrBox	= SimpleImage::imageTrimBox($rImage, $strBackgroundColor);

		// Resize and crop
		$rNewImage	= imagecreatetruecolor($arrBox['w'], $arrBox['h']);

		// Preserve alphatransparency in PNGs
		imagealphablending($rNewImage, false);
		imagesavealpha($rNewImage, true);

		imagecopyresampled($rNewImage, $rImage, 0, 0, $arrBox['l'], $arrBox['t'], $arrBox['w'], $arrBox['h'], $arrBox['w'], $arrBox['h']);

		return $oImage->save($rNewImage, $strSource, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Get trim box for image, used for shrinking to non-white
	 *
	 * @param resource        $rImage
	 * @param string|int|null $mHexColor
	 *
	 * @return array
	 */
	public static function imageTrimBox($rImage, $mHexColor = null): array
	{
		if(ctype_xdigit($mHexColor))
		{
			$iColor	= $mHexColor;
		}
		else
		{
			$iColor	= imagecolorat($rImage, 0, 0);
		}

		$iOriginalWidth		= imagesx($rImage);
		$iOriginalHeight	= imagesy($rImage);
		$iNewWidth			= $iOriginalWidth;
		$iNewHeight			= $iOriginalHeight;
		$iTop				= 0;
		$iBottom			= $iOriginalHeight;
		$iLeft				= 0;
		$iRight				= $iOriginalWidth;

		do
		{
			//top
			for(; $iTop < $iOriginalHeight; ++$iTop)
			{
				for($iX = 0; $iX < $iOriginalWidth; ++$iX)
				{
					if(imagecolorat($rImage, $iX, $iTop) !== $iColor)
					{
						break 2;
					}
				}
			}

			// stop if all pixels are trimmed
			if($iTop === $iBottom)
			{
				$iTop			= 0;
				$iResultCode	= 2;
				break 1;
			}

			// bottom
			for(; $iBottom > 0; --$iBottom)
			{
				for($iX = 0; $iX < $iOriginalWidth; ++$iX)
				{
					if(imagecolorat($rImage, $iX, $iBottom - 1) !== $iColor)
					{
						break 2;
					}
				}
			}

			// left
			for(; $iLeft < $iOriginalWidth; ++$iLeft)
			{
				for($iY = $iTop; $iY < $iBottom; ++$iY)
				{
					if(imagecolorat($rImage, $iLeft, $iY) !== $iColor)
					{
						break 2;
					}
				}
			}

			// right
			for(; $iRight > 0; --$iRight)
			{
				for($iY = $iTop; $iY < $iBottom; ++$iY)
				{
					if(imagecolorat($rImage, $iRight - 1, $iY) !== $iColor)
					{
						break 2;
					}
				}
			}

			$iNewWidth		= $iRight - $iLeft;
			$iNewHeight		= $iBottom - $iTop;
			$iResultCode	= ($iNewWidth < $iOriginalWidth || $iNewHeight < $iOriginalHeight) ? 1 : 0;
		}
		while(0);

		// Result codes:
		// 0 = Trim Zero Pixels
		// 1 = Trim Some Pixels
		// 2 = Trim All Pixels
		return [
			'#'		=> $iResultCode,
			'l'		=> $iLeft,
			't'		=> $iTop,
			'r'		=> $iRight,
			'b'		=> $iBottom,
			'w'		=> $iNewWidth,
			'h'		=> $iNewHeight,
			'w1'	=> $iOriginalWidth,
			'h1'	=> $iOriginalHeight,
		];
	}

	/**
	 * Crop an image and optionally resize the resulting piece
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int      $iX1
	 * @param int      $iY1
	 * @param int      $iX2
	 * @param int      $iY2
	 * @param int|null $iNewWidth
	 * @param int|null $iNewHeight
	 * @param bool     $bResample
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function crop(
		string	$strSource,
		string	$strDestination,
		int		$iX1,
		int		$iY1,
		int		$iX2,
		int		$iY2,
		?int	$iNewWidth		= null,
		?int	$iNewHeight		= null,
		bool	$bResample		= true,
		?int	$iQuality		= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Crop size
		if($iX2 < $iX1)
		{
			[$iX1, $iX2]	= [$iX2, $iX1];
		}
		if($iY2 < $iY1)
		{
			[$iY1, $iY2]	= [$iY2, $iY1];
		}
		$iCropWidth		= $iX2 - $iX1;
		$iCropHeight	= $iY2 - $iY1;

		if($iNewWidth === null)
		{
			$iNewWidth	= $iCropWidth;
		}
		if($iNewHeight === null)
		{
			$iNewHeight	= $iCropHeight;
		}

		$rNew	= imagecreatetruecolor($iNewWidth, $iNewHeight);

		// Preserve alphatransparency in PNGs
		imagealphablending($rNew, false);
		imagesavealpha($rNew, true);

		// Create the new image
		if($bResample)
		{
			imagecopyresampled($rNew, $rOriginal, 0, 0, $iX1, $iY1, $iNewWidth, $iNewHeight, $iCropWidth, $iCropHeight);
		}
		else
		{
			imagecopyresized($rNew, $rOriginal, 0, 0, $iX1, $iY1, $iNewWidth, $iNewHeight, $iCropWidth, $iCropHeight);
		}

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Trim the edges of a portrait or landscape image to make it square and optionally resize the resulting image
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param int|null $iNewSize
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function square_crop(
		string	$strSource,
		string	$strDestination,
		?int	$iNewSize	= null,
		?int	$iQuality	= null
	): bool
	{
		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		// Calculate measurements
		if($arrInfo[0] > $arrInfo[1])
		{
			// For landscape images
			$iOffsetX		= ($arrInfo[0] - $arrInfo[1]) / 2;
			$iOffsetY		= 0;
			$iSquareSize	= $arrInfo[0] - ($iOffsetX * 2);
		}
		else
		{
			// For portrait and square images
			$iOffsetX		= 0;
			$iOffsetY		= ($arrInfo[1] - $arrInfo[0]) / 2;
			$iSquareSize	= $arrInfo[1] - ($iOffsetY * 2);
		}

		if($iNewSize === null)
		{
			$iNewSize	= $iSquareSize;
		}

		// Resize and crop
		$rNew	= imagecreatetruecolor($iNewSize, $iNewSize);

		// Preserve alphatransparency in PNGs
		imagealphablending($rNew, false);
		imagesavealpha($rNew, true);

		imagecopyresampled($rNew, $rOriginal, 0, 0, $iOffsetX, $iOffsetY, $iNewSize, $iNewSize, $iSquareSize, $iSquareSize);

		return $oImage->save($rNew, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Overlay an image on top of another image with opacity; works with 24-bit PNG alpha-transparency
	 *
	 * @param string   $strSource
	 * @param string   $strDestination
	 * @param string   $strWatermarkSource
	 * @param string   $strPosition
	 * @param int      $iOpacity
	 * @param int      $iMargin
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	public static function watermark(
		string	$strSource,
		string	$strDestination,
		string	$strWatermarkSource,
		string	$strPosition	= 'center',
		int		$iOpacity		= 50,
		int		$iMargin		= 0,
		?int	$iQuality		= null
	): bool
	{
		$oImage								= new SimpleImage;
		[$rOriginal, $arrInfo]				= $oImage->load($strSource);
		[$rWatermark, $arrWatermarkInfo]	= $oImage->load($strWatermarkSource);

		switch(strtolower($strPosition))
		{
			case 'top-left':
			case 'left-top':
				$iX	= 0 + $iMargin;
				$iY	= 0 + $iMargin;
				break;

			case 'top-right':
			case 'right-top':
				$iX	= $arrInfo[0] - $arrWatermarkInfo[0] - $iMargin;
				$iY	= 0 + $iMargin;
				break;

			case 'top':
			case 'top-center':
			case 'center-top':
				$iX	= ($arrInfo[0] / 2) - ($arrWatermarkInfo[0] / 2);
				$iY	= 0 + $iMargin;
				break;

			case 'bottom-left':
			case 'left-bottom':
				$iX	= 0 + $iMargin;
				$iY	= $arrInfo[1] - $arrWatermarkInfo[1] - $iMargin;
				break;

			case 'bottom-right':
			case 'right-bottom':
				$iX	= $arrInfo[0] - $arrWatermarkInfo[0] - $iMargin;
				$iY	= $arrInfo[1] - $arrWatermarkInfo[1] - $iMargin;
				break;

			case 'bottom':
			case 'bottom-center':
			case 'center-bottom':
				$iX	= ($arrInfo[0] / 2) - ($arrWatermarkInfo[0] / 2);
				$iY	= $arrInfo[1] - $arrWatermarkInfo[1] - $iMargin;
				break;

			case 'left':
			case 'center-left':
			case 'left-center':
				$iX	= 0 + $iMargin;
				$iY	= ($arrInfo[1] / 2) - ($arrWatermarkInfo[1] / 2);
				break;

			case 'right':
			case 'center-right':
			case 'right-center':
				$iX	= $arrInfo[0] - $arrWatermarkInfo[0] - $iMargin;
				$iY	= ($arrInfo[1] / 2) - ($arrWatermarkInfo[1] / 2);
				break;

			case 'center':
			default:
				$iX	= ($arrInfo[0] / 2) - ($arrWatermarkInfo[0] / 2);
				$iY	= ($arrInfo[1] / 2) - ($arrWatermarkInfo[1] / 2);
				break;
		}

		$oImage->imagecopymerge_alpha($rOriginal, $rWatermark, $iX, $iY, 0, 0, $arrWatermarkInfo[0], $arrWatermarkInfo[1], $iOpacity);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}

	/**
	 * Adds text on top of an image with optional shadow
	 *
	 * @param string      $strSource
	 * @param string      $strDestination
	 * @param string      $strText
	 * @param string      $strFontFile
	 * @param int         $iSize
	 * @param string      $strColor
	 * @param string      $strPosition
	 * @param int         $iMargin
	 * @param string|null $strShadowColor
	 * @param int         $iShadowOffsetX
	 * @param int         $iShadowOffsetY
	 * @param int|null    $iQuality
	 *
	 * @return bool
	 */
	public static function text(
		string	$strSource,
		string	$strDestination,
		string	$strText,
		string	$strFontFile,
		int		$iSize			= 12,
		string	$strColor		= '#000000',
		string	$strPosition	= 'center',
		int		$iMargin		= 0,
		?string	$strShadowColor	= null,
		int		$iShadowOffsetX	= 0,
		int		$iShadowOffsetY	= 0,
		?int	$iQuality		= null
	): bool
	{
		// This method could be improved to support the text angle
		$iAngle	= 0;

		$oImage					= new SimpleImage;
		[$rOriginal, $arrInfo]	= $oImage->load($strSource);

		$arrRgb		= $oImage->hex2rgb($strColor);
		$strColor	= imagecolorallocate($rOriginal, $arrRgb['r'], $arrRgb['g'], $arrRgb['b']);

		// Determine text size
		$arrBox	= imagettfbbox($iSize, $iAngle, $strFontFile, $strText);

		// Horizontal
		$iTextWidth		= abs($arrBox[6] - $arrBox[2]);
		$iTextHeight	= abs($arrBox[7] - $arrBox[3]);


		switch(strtolower($strPosition))
		{
			case 'top-left':
			case 'left-top':
				$iX	= 0 + $iMargin;
				$iY	= 0 + $iSize + $iMargin;
				break;

			case 'top-right':
			case 'right-top':
				$iX	= $arrInfo[0] - $iTextWidth - $iMargin;
				$iY	= 0 + $iSize + $iMargin;
				break;

			case 'top':
			case 'top-center':
			case 'center-top':
				$iX	= ($arrInfo[0] / 2) - ($iTextWidth / 2);
				$iY	= 0 + $iSize + $iMargin;
				break;

			case 'bottom-left':
			case 'left-bottom':
				$iX	= 0 + $iMargin;
				$iY	= $arrInfo[1] - $iTextHeight - $iMargin + $iSize;
				break;

			case 'bottom-right':
			case 'right-bottom':
				$iX	= $arrInfo[0] - $iTextWidth - $iMargin;
				$iY	= $arrInfo[1] - $iTextHeight - $iMargin + $iSize;
				break;

			case 'bottom':
			case 'bottom-center':
			case 'center-bottom':
				$iX	= ($arrInfo[0] / 2) - ($iTextWidth / 2);
				$iY	= $arrInfo[1] - $iTextHeight - $iMargin + $iSize;
				break;

			case 'left':
			case 'center-left':
			case 'left-center':
				$iX	= 0 + $iMargin;
				$iY	= ($arrInfo[1] / 2) - (($iTextHeight / 2) - $iSize);
				break;

			case 'right';
			case 'center-right':
			case 'right-center':
				$iX	= $arrInfo[0] - $iTextWidth - $iMargin;
				$iY	= ($arrInfo[1] / 2) - (($iTextHeight / 2) - $iSize);
				break;

			case 'center':
			default:
				$iX	= ($arrInfo[0] / 2) - ($iTextWidth / 2);
				$iY	= ($arrInfo[1] / 2) - (($iTextHeight / 2) - $iSize);
				break;
		}

		if($strShadowColor)
		{
			$arrRgb			= $oImage->hex2rgb($strShadowColor);
			$strShadowColor	= imagecolorallocate($rOriginal, $arrRgb['r'], $arrRgb['g'], $arrRgb['b']);
			imagettftext($rOriginal, $iSize, $iAngle, $iX + $iShadowOffsetX, $iY + $iShadowOffsetY, $strShadowColor, $strFontFile, $strText);
		}

		imagettftext($rOriginal, $iSize, $iAngle, $iX, $iY, $strColor, $strFontFile, $strText);

		return $oImage->save($rOriginal, $strDestination, $arrInfo['mime'], $iQuality);
	}
}
