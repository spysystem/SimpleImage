<?php
namespace SpyClaviska\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use SpyClaviska\SimpleImage;

/**
 * Class SimpleImageTest
 *
 * @package SpyClaviska\Tests
 */
class SimpleImageTest extends TestCase
{
	/**
	 * @param string $strFilePath
	 *
	 * @return resource|bool
	 */
	private static function LoadImage(string $strFilePath)
	{
		switch(pathinfo($strFilePath, PATHINFO_EXTENSION))
		{
			case 'gif':
				return imagecreatefromgif($strFilePath);
				break;

			case 'jpg':
				return imagecreatefromjpeg($strFilePath);
				break;

			case 'png':
				return imagecreatefrompng($strFilePath);
				break;
		}

		self::fail('Unsupported file type: '.$strFilePath);

		return false;
	}

	/**
	 * @param resource $rImage
	 * @param string   $strFilePath
	 *
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	private static function SaveImage($rImage, string $strFilePath, ?int $iQuality = null): bool
	{
		switch(pathinfo($strFilePath, PATHINFO_EXTENSION))
		{
			case 'gif':
				return imagegif($rImage, $strFilePath);
				break;

			case 'jpg':
				if($iQuality == null)
				{
					$iQuality = 85;
				}
				if($iQuality < 0)
				{
					$iQuality = 0;
				}
				if($iQuality > 100)
				{
					$iQuality = 100;
				}

				return imagejpeg($rImage, $strFilePath, $iQuality);
				break;

			case 'png':
				if($iQuality == null)
				{
					$iQuality = 9;
				}
				if($iQuality > 9)
				{
					$iQuality = 9;
				}
				if($iQuality < 1)
				{
					$iQuality = 0;
				}

				return imagepng($rImage, $strFilePath, $iQuality);
				break;
		}

		self::fail('Unsupported file type: '.$strFilePath);

		return false;
	}

	/**
	 * Generate and save a random image
	 *
	 * @param string   $strFilePath
	 * @param int      $iWidth
	 * @param int      $iHeight
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	private static function GenerateRandomImage(string $strFilePath, int $iWidth = 100, int $iHeight = 100, ?int $iQuality = null): bool
	{
		$rImage	= imagecreatetruecolor($iWidth, $iHeight);

		$iColor	= imagecolorallocate($rImage, 255, 255, 255);
		$iX1	= 0;
		$iY1	= 0;
		$iX2	= $iWidth;
		$iY2	= $iHeight;
		imagefilledrectangle($rImage, $iX1, $iY1, $iX2, $iY2, $iColor);

		for($iCount = 0; $iCount <= 5; $iCount++)
		{
			$iColor	= imagecolorallocate($rImage, rand(0, 255), rand(0, 255), rand(0, 255));
			$iX1	= rand(5, $iWidth / 2);
			$iY1	= rand(5, $iHeight / 2);
			$iX2	= rand($iX1, $iWidth - 5);
			$iY2	= rand($iY1, $iHeight - 5);
			imagefilledrectangle($rImage, $iX1, $iY1, $iX2, $iY2, $iColor);
		}

		return self::SaveImage($rImage, $strFilePath, $iQuality);
	}

	/**
	 * Generate and save a white image
	 *
	 * @param string   $strFilePath
	 * @param int      $iWidth
	 * @param int      $iHeight
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	private static function GenerateWhiteImage(string $strFilePath, int $iWidth = 100, int $iHeight = 100, ?int $iQuality = null): bool
	{
		$rImage	= imagecreatetruecolor($iWidth, $iHeight);

		$iWhite	= imagecolorallocate($rImage, 255, 255, 255);
		$iX1	= 0;
		$iY1	= 0;
		$iX2	= $iWidth;
		$iY2	= $iHeight;
		imagefilledrectangle($rImage, $iX1, $iY1, $iX2, $iY2, $iWhite);

		return self::SaveImage($rImage, $strFilePath, $iQuality);
	}

	/**
	 * Generate and save a white image with black center
	 *
	 * @param string   $strFilePath
	 * @param int      $iWidth
	 * @param int      $iHeight
	 * @param int      $iCenterWidth
	 * @param int      $iCenterHeight
	 * @param int|null $iQuality
	 *
	 * @return bool
	 */
	private static function GenerateWhiteImageWithBlackCenter(string $strFilePath, int $iWidth = 100, int $iHeight = 100, int $iCenterWidth = 50, int $iCenterHeight = 50, ?int $iQuality = null): bool
	{
		$rImage = imagecreatetruecolor($iWidth, $iHeight);

		$iColor	= imagecolorallocate($rImage, 255, 255, 255);
		$iX1	= 0;
		$iY1	= 0;
		$iX2	= $iWidth;
		$iY2	= $iHeight;
		imagefilledrectangle($rImage, $iX1, $iY1, $iX2, $iY2, $iColor);

		$iColor	= imagecolorallocate($rImage, 0, 0, 0);
		$iX1	= (int)($iWidth / 2 - ($iCenterWidth / 2));
		$iY1	= (int)($iHeight / 2 - ($iCenterHeight / 2));
		$iX2	= ($iX1 + $iCenterWidth - 1);
		$iY2	= ($iY1 + $iCenterHeight - 1);
		imagefilledrectangle($rImage, $iX1, $iY1, $iX2, $iY2, $iColor);

		return self::SaveImage($rImage, $strFilePath, $iQuality);
	}

	/**
	 * Get image quality from a file object
	 *
	 * @param string $strFilePath
	 * @return int
	 */
	private static function GetImageQuality(string $strFilePath): int
	{
		$iQuality	= 0;

		// Extract from image exif data
		if(pathinfo($strFilePath, PATHINFO_EXTENSION) === 'jpg')
		{
			$arrImageExifData	= exif_read_data($strFilePath);
			if(!empty($arrImageExifData['COMMENT'][0]) && preg_match('/quality = ([0-9]+)/', $arrImageExifData['COMMENT'][0], $arrMatches))
			{
				$iQuality	= (int)$arrMatches[1];
			}
		}

		return $iQuality;
	}

	/**
	 * @param string   $strSourceFilePath
	 * @param string   $strDestinationFilePath
	 * @param int      $iMode
	 * @param int|null $iQuality
	 */
	private static function AssertImageFlipped(string $strSourceFilePath, string $strDestinationFilePath, int $iMode, ?int $iQuality = null): void
	{
		try
		{
			$strTemporaryFilePath	= './assert-image-flipped.'.pathinfo($strSourceFilePath, PATHINFO_EXTENSION);

			$rImage	= self::LoadImage($strSourceFilePath);
			imageflip($rImage, $iMode);
			self::assertTrue(self::SaveImage($rImage, $strTemporaryFilePath, $iQuality));

			self::assertFileEquals($strTemporaryFilePath, $strDestinationFilePath);

			unlink($strTemporaryFilePath);
		}
		catch(Exception $oException)
		{
			self::fail($oException->getMessage());
		}
	}

	/**
	 * @param string   $strSourceFilePath
	 * @param string   $strDestinationFilePath
	 * @param int      $iAngle
	 * @param int      $iBackgroundColor
	 * @param int|null $iQuality
	 */
	private static function AssertImageRotated(string $strSourceFilePath, string $strDestinationFilePath, int $iAngle, int $iBackgroundColor = 0, ?int $iQuality = null): void
	{
		try
		{
			$strTemporaryFilePath	= './assert-image-rotated.'.pathinfo($strSourceFilePath, PATHINFO_EXTENSION);

			$rImage	= self::LoadImage($strSourceFilePath);
			$rImage	= imagerotate($rImage, $iAngle, $iBackgroundColor);
			self::assertTrue(self::SaveImage($rImage, $strTemporaryFilePath, $iQuality));

			self::assertFileEquals($strTemporaryFilePath, $strDestinationFilePath);

			unlink($strTemporaryFilePath);
		}
		catch(Exception $oException)
		{
			self::fail($oException->getMessage());
		}
	}

	/**
	 * @param string   $strSourceFilePath
	 * @param string   $strDestinationFilePath
	 * @param int      $iFilterType
	 * @param int|null $iArg1
	 * @param int|null $iArg2
	 * @param int|null $iArg3
	 * @param int|null $iArg4
	 * @param int|null $iQuality
	 */
	private static function AssertImageFilterApplied(string $strSourceFilePath, string $strDestinationFilePath, int $iFilterType, ?int $iArg1 = null, ?int $iArg2 = null, ?int $iArg3 = null, ?int $iArg4 = null, ?int $iQuality = null): void
	{
		try
		{
			$strTemporaryFilePath	= './assert-image-filter-applied.'.pathinfo($strSourceFilePath, PATHINFO_EXTENSION);
			$rImage					= self::LoadImage($strSourceFilePath);
			imagefilter($rImage, $iFilterType, ...array_filter([$iArg1, $iArg2, $iArg3, $iArg4], static function($mValue): bool {
				return $mValue !== null;
			}));
			self::assertTrue(self::SaveImage($rImage, $strTemporaryFilePath, $iQuality));
			self::assertFileEquals($strTemporaryFilePath, $strDestinationFilePath);
			unlink($strTemporaryFilePath);
		}
		catch(Exception $oException)
		{
			self::fail($oException->getMessage());
		}
	}

	/**
	 * @param string $strExpectedHexColor
	 * @param string $strActualHexColor
	 * @param int    $iTolerance
	 */
	private static function AssertSimilarColor(string $strExpectedHexColor, string $strActualHexColor, int $iTolerance = 35): void
	{
		$strExpectedHex	= ltrim($strExpectedHexColor, '#');
		$strActualHex	= ltrim($strActualHexColor, '#');

		$arrExpectedRGB = [
			'r'	=> hexdec(substr($strExpectedHex, 0, 2)),
			'g'	=> hexdec(substr($strExpectedHex, 2, 2)),
			'b'	=> hexdec(substr($strExpectedHex, 4, 2)),
		];
		$arrActualRGB = [
			'r'	=> hexdec(substr($strActualHex, 0, 2)),
			'g'	=> hexdec(substr($strActualHex, 2, 2)),
			'b'	=> hexdec(substr($strActualHex, 4, 2)),
		];

		$bIsSimilar	=
			(
				$arrExpectedRGB['r'] >= $arrActualRGB['r'] - $iTolerance
			 &&	$arrExpectedRGB['r'] <= $arrActualRGB['r'] + $iTolerance
			)
		 &&	(
		 		$arrExpectedRGB['g'] >= $arrActualRGB['g'] - $iTolerance
			 &&	$arrExpectedRGB['g'] <= $arrActualRGB['g'] + $iTolerance
			)
		 &&	(
		 		$arrExpectedRGB['b'] >= $arrActualRGB['b'] - $iTolerance
			 &&	$arrExpectedRGB['b'] <= $arrActualRGB['b'] + $iTolerance
			)
		;

		if(!$bIsSimilar)
		{
			self::fail(<<<MSG
			Failed asserting that two colors are similar
			Expected : {$strExpectedHexColor}
			Actual   : {$strActualHexColor}
			MSG);
		}
	}

	public function testQuality(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-quality.jpg';

		self::GenerateWhiteImage($strSourceFilePath, 100, 100, 100);
		self::assertEquals(100, self::GetImageQuality($strSourceFilePath));

		$arrData	= [
			200		=> 100,
			100		=> 100,
			75		=> 75,
			50		=> 50,
			25		=> 25,
			0		=> 0,
			-100	=> 0,
		];

		foreach($arrData as $iQuality => $iExpected)
		{
			self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 0, '#FFFFFF', $iQuality));
			self::assertEquals($iExpected, self::GetImageQuality($strDestinationFilePath));
		}

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testConvert(): void
	{
		// Test GIF to JPG and PNG
		$strSourceFilePath	= './test.gif';
		self::GenerateWhiteImage($strSourceFilePath);

		$strDestinationFilePath	= './test-convert.jpg';
		self::assertTrue(SimpleImage::convert($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals('image/jpeg', mime_content_type($strDestinationFilePath));
		unlink($strDestinationFilePath);

		$strDestinationFilePath	= './test-convert.png';
		self::assertTrue(SimpleImage::convert($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals('image/png', mime_content_type($strDestinationFilePath));
		unlink($strDestinationFilePath);

		unlink($strSourceFilePath);

		// Test JPG to GIF and PNG
		$strSourceFilePath	= './test.jpg';
		self::GenerateWhiteImage($strSourceFilePath);

		$strDestinationFilePath	= './test-convert.gif';
		self::assertTrue(SimpleImage::convert($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals('image/gif', mime_content_type($strDestinationFilePath));
		unlink($strDestinationFilePath);

		$strDestinationFilePath	= './test-convert.png';
		self::assertTrue(SimpleImage::convert($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals('image/png', mime_content_type($strDestinationFilePath));
		unlink($strDestinationFilePath);

		unlink($strSourceFilePath);

		// Test PNG to GIF and JPG
		$strSourceFilePath	= './test.png';
		self::GenerateWhiteImage($strSourceFilePath);

		$strDestinationFilePath	= './test-convert.gif';
		self::assertTrue(SimpleImage::convert($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals('image/gif', mime_content_type($strDestinationFilePath));
		unlink($strDestinationFilePath);

		$strDestinationFilePath	= './test-convert.jpg';
		self::assertTrue(SimpleImage::convert($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals('image/jpeg', mime_content_type($strDestinationFilePath));
		unlink($strDestinationFilePath);

		unlink($strSourceFilePath);
	}

	public function testFlip(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-flip.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::flip($strSourceFilePath, $strDestinationFilePath, 'vertical'));
		self::AssertImageFlipped($strSourceFilePath, $strDestinationFilePath, IMG_FLIP_VERTICAL);
		self::assertTrue(SimpleImage::flip($strSourceFilePath, $strDestinationFilePath, 'v'));
		self::AssertImageFlipped($strSourceFilePath, $strDestinationFilePath, IMG_FLIP_VERTICAL);
		self::assertTrue(SimpleImage::flip($strSourceFilePath, $strDestinationFilePath, 'y'));
		self::AssertImageFlipped($strSourceFilePath, $strDestinationFilePath, IMG_FLIP_VERTICAL);

		self::assertTrue(SimpleImage::flip($strSourceFilePath, $strDestinationFilePath, 'horizontal'));
		self::AssertImageFlipped($strSourceFilePath, $strDestinationFilePath, IMG_FLIP_HORIZONTAL);
		self::assertTrue(SimpleImage::flip($strSourceFilePath, $strDestinationFilePath, 'h'));
		self::AssertImageFlipped($strSourceFilePath, $strDestinationFilePath, IMG_FLIP_HORIZONTAL);
		self::assertTrue(SimpleImage::flip($strSourceFilePath, $strDestinationFilePath, 'x'));
		self::AssertImageFlipped($strSourceFilePath, $strDestinationFilePath, IMG_FLIP_HORIZONTAL);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testRotate(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-rotate.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 45, '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 45, 0);
		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 90, '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 90, 0);
		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 180, '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 180, 0);
		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 270, '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 270, 0);
		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 360, '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 360, 0);

		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 'clockwise', '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 270, 0);
		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 'cw', '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 270, 0);

		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 'counterclockwise', '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 90, 0);
		self::assertTrue(SimpleImage::rotate($strSourceFilePath, $strDestinationFilePath, 'ccw', '#000000'));
		self::AssertImageRotated($strSourceFilePath, $strDestinationFilePath, 90, 0);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testGrayscale(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-greyscale.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::grayscale($strSourceFilePath, $strDestinationFilePath));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_GRAYSCALE);

		self::assertTrue(SimpleImage::grayscale($strSourceFilePath, $strDestinationFilePath, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_GRAYSCALE, null, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testInvert(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-invert.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::invert($strSourceFilePath, $strDestinationFilePath));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_NEGATE);

		self::assertTrue(SimpleImage::invert($strSourceFilePath, $strDestinationFilePath, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_NEGATE, null, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testBrightness(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-brightness.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::brightness($strSourceFilePath, $strDestinationFilePath, -255));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_BRIGHTNESS, -255);

		self::assertTrue(SimpleImage::brightness($strSourceFilePath, $strDestinationFilePath, 0));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_BRIGHTNESS, 0);

		self::assertTrue(SimpleImage::brightness($strSourceFilePath, $strDestinationFilePath, 255));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_BRIGHTNESS, 255);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testContrast(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-contrast.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::contrast($strSourceFilePath, $strDestinationFilePath, 100));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_CONTRAST, 100);

		self::assertTrue(SimpleImage::contrast($strSourceFilePath, $strDestinationFilePath, 0));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_CONTRAST, 0);

		self::assertTrue(SimpleImage::contrast($strSourceFilePath, $strDestinationFilePath, -100));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_CONTRAST, -100);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testColorize(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-colorize.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::colorize($strSourceFilePath, $strDestinationFilePath, -255, -255, -255, 0));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_COLORIZE, -255, -255, -255, 0);

		self::assertTrue(SimpleImage::colorize($strSourceFilePath, $strDestinationFilePath, -255, -255, -255, 0.5));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_COLORIZE, -255, -255, -255, 0.5);

		self::assertTrue(SimpleImage::colorize($strSourceFilePath, $strDestinationFilePath, 0, 0, 0, 0));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_COLORIZE, 0, 0, 0, 0);

		self::assertTrue(SimpleImage::colorize($strSourceFilePath, $strDestinationFilePath, 0, 0, 0, 0.5));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_COLORIZE, 0, 0, 0, 0.5);

		self::assertTrue(SimpleImage::colorize($strSourceFilePath, $strDestinationFilePath, 255, 255, 255, 0));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_COLORIZE, 255, 255, 255, 0);

		self::assertTrue(SimpleImage::colorize($strSourceFilePath, $strDestinationFilePath, 255, 255, 255, 0.5));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_COLORIZE, 255, 255, 255, 0.5);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testEdgeDetect(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-edge-detect.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::edgedetect($strSourceFilePath, $strDestinationFilePath));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_EDGEDETECT);

		self::assertTrue(SimpleImage::edgedetect($strSourceFilePath, $strDestinationFilePath, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_EDGEDETECT, null, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testEmboss(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-emboss.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::emboss($strSourceFilePath, $strDestinationFilePath));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_EMBOSS);

		self::assertTrue(SimpleImage::emboss($strSourceFilePath, $strDestinationFilePath, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_EMBOSS, null, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testBlur(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-blur.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::blur($strSourceFilePath, $strDestinationFilePath));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_GAUSSIAN_BLUR);

		self::assertTrue(SimpleImage::blur($strSourceFilePath, $strDestinationFilePath, 1, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_GAUSSIAN_BLUR, null, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testSketch(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-sketch.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::sketch($strSourceFilePath, $strDestinationFilePath));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_MEAN_REMOVAL);

		self::assertTrue(SimpleImage::sketch($strSourceFilePath, $strDestinationFilePath, 1, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_MEAN_REMOVAL, null, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testSmooth(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-smooth.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::smooth($strSourceFilePath, $strDestinationFilePath, 1));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_SMOOTH, 1);

		self::assertTrue(SimpleImage::smooth($strSourceFilePath, $strDestinationFilePath, 1, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_SMOOTH, 1, null, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testPixelate(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-pixelate.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 5, false));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 5, false);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 5, false, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 5, false, null, null, 50);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 5, true));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 5, true);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 5, true, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 5, true, null, null, 50);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 10, false));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 10, false);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 10, false, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 10, false, null, null, 50);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 10, true));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 10, true);

		self::assertTrue(SimpleImage::pixelate($strSourceFilePath, $strDestinationFilePath, 10, true, 50));
		self::AssertImageFilterApplied($strSourceFilePath, $strDestinationFilePath, IMG_FILTER_PIXELATE, 10, true, null, null, 50);

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testSepia(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-sepia.jpg';
		self::GenerateRandomImage($strSourceFilePath);

		self::assertTrue(SimpleImage::sepia($strSourceFilePath, $strDestinationFilePath));
		try
		{
			$strTemporaryFilePath	= './assert-image-filter-applied.'.pathinfo($strSourceFilePath, PATHINFO_EXTENSION);
			$rImage					= self::LoadImage($strSourceFilePath);
			imagefilter($rImage, IMG_FILTER_GRAYSCALE);
			imagefilter($rImage, IMG_FILTER_COLORIZE, 90, 60, 30);
			self::assertTrue(self::SaveImage($rImage, $strTemporaryFilePath));
			self::assertFileEquals($strTemporaryFilePath, $strDestinationFilePath);
			unlink($strTemporaryFilePath);
		}
		catch(Exception $oException)
		{
			self::fail($oException->getMessage());
		}

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testResize(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-resize.jpg';
		self::GenerateWhiteImage($strSourceFilePath, 100, 100);

		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 100, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 75, 75, false));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 50, 50, false));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 100, 100, true));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 75, 75, true));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 50, 50, true));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 100, 100, true, 50));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 75, 75, true, 50));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize($strSourceFilePath, $strDestinationFilePath, 50, 50, true, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testResizeToWidth(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-resize-to-width.jpg';
		self::GenerateWhiteImage($strSourceFilePath, 100, 100);

		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 75, false));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 50, false));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 100, true));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 75, true));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 50, true));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 100, true, 50));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 75, true, 50));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_width($strSourceFilePath, $strDestinationFilePath, 50, true, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testResizeToHeight(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-resize-to-height.jpg';
		self::GenerateWhiteImage($strSourceFilePath, 100, 100);

		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 75, false));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 50, false));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 100, true));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 75, true));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 50, true));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 100, true, 50));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 75, true, 50));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::resize_to_height($strSourceFilePath, $strDestinationFilePath, 50, true, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testShrinkToFit(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-shrink-to-fit.jpg';
		self::GenerateWhiteImage($strSourceFilePath, 100, 100);

		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 100, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 75, 75, false));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 50, 50, false));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 100, 100, true));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 75, 75, true));
		self::assertEquals([75, 75], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 50, 50, true));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 100, 200, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 200, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_fit($strSourceFilePath, $strDestinationFilePath, 200, 200, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testShrinkToSize(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-shrink-to-size.jpg';

		$arrGenerateImageSizes	= [
			// Landscape
			[300, 150],
			// Portrait
			[300, 450],
			// Square
			[300, 300],
		];

		$arrShrinkToSizes	= [
			// Landscape
			[100, 50],
			[200, 100],
			[300, 150],
			[400, 200],
			[500, 250],
			// Portrait
			[100, 150],
			[200, 300],
			[300, 450],
			[400, 600],
			[500, 750],
			// Square
			[100, 100],
			[200, 200],
			[300, 300],
			[400, 400],
			[500, 500],
		];

		foreach($arrGenerateImageSizes as $arrGenerateImageSize)
		{
			self::GenerateRandomImage($strSourceFilePath, $arrGenerateImageSize[0], $arrGenerateImageSize[1], 100);

			foreach($arrShrinkToSizes as $arrShrinkToSize)
			{
				// Validate size without resampling
				self::assertTrue(SimpleImage::shrinkToSize($strSourceFilePath, $strDestinationFilePath, $arrShrinkToSize[0], $arrShrinkToSize[1], false));
				self::assertEquals([$arrShrinkToSize[0], $arrShrinkToSize[1]], array_slice(getimagesize($strDestinationFilePath), 0, 2));

				// Validate size with resampling
				self::assertTrue(SimpleImage::shrinkToSize($strSourceFilePath, $strDestinationFilePath, $arrShrinkToSize[0], $arrShrinkToSize[1], true));
				self::assertEquals([$arrShrinkToSize[0], $arrShrinkToSize[1]], array_slice(getimagesize($strDestinationFilePath), 0, 2));

				// Validate added/missing whitespace ("blackspace" in this test)
				self::assertTrue(SimpleImage::shrinkToSize($strSourceFilePath, $strDestinationFilePath, $arrShrinkToSize[0], $arrShrinkToSize[1], true, 100, 'image/png', false, '#000000'));

				// Get aspect ratio for generated and shrinked image
				// < 1.0 - Portait image
				// = 1.0 - Square image
				// > 1.0 - Landscape image
				$fGenerateRatio	= $arrGenerateImageSize[0] / $arrGenerateImageSize[1];
				$fShrinkRatio	= $arrShrinkToSize[0] / $arrShrinkToSize[1];

				// By default expect white on all sides (ie. no "blackspace")
				$arrImageSideColors		= [
					'top'		=> '#FFFFFF',
					'right'		=> '#FFFFFF',
					'bottom'	=> '#FFFFFF',
					'left'		=> '#FFFFFF',
				];

				// If generated image is smaller than shrinked image, expect "blackspace" on all sides
				if($arrGenerateImageSize[0] < $arrShrinkToSize[0] && $arrGenerateImageSize[1] < $arrShrinkToSize[1])
				{
					$arrImageSideColors['top']		= '#000000';
					$arrImageSideColors['right']	= '#000000';
					$arrImageSideColors['bottom']	= '#000000';
					$arrImageSideColors['left']		= '#000000';
				}
				// If generated image ratio is smaller than shrinked image ratio, expect "blackspace" on right and left
				elseif($fGenerateRatio < $fShrinkRatio)
				{
					$arrImageSideColors['right']	= '#000000';
					$arrImageSideColors['left']		= '#000000';
				}
				// If generated image ratio is bigger than shrinked image ratio, expect "blackspace" on top and bottom
				elseif($fGenerateRatio > $fShrinkRatio)
				{
					$arrImageSideColors['top']		= '#000000';
					$arrImageSideColors['bottom']	= '#000000';
				}

				// Validate image side colors
				foreach($arrImageSideColors as $strSide => $strColor)
				{
					$iPosX	= 0;
					$iPosY	= 0;
					switch($strSide)
					{
						case 'top':
							$iPosX	= (int)floor($arrShrinkToSize[0] / 2);
							break;

						case 'right':
							$iPosX	= $arrShrinkToSize[0] - 1;
							$iPosY	= (int)floor($arrShrinkToSize[1] / 2);
							break;

						case 'bottom':
							$iPosX	= (int)floor($arrShrinkToSize[0] / 2);
							$iPosY	= $arrShrinkToSize[1] - 1;
							break;

						case 'left':
							$iPosY	= (int)floor($arrShrinkToSize[1] / 2);
							break;
					}

					// Check if color is similar within the given tolerance, since the shrinked image might be "dirty"
					self::AssertSimilarColor($strColor, SimpleImage::get_color_at_position($strDestinationFilePath, $iPosX, $iPosY), 10);
				}
			}
		}

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testShrinkToSquare(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-shrink-to-square.jpg';
		self::GenerateWhiteImage($strSourceFilePath, 100, 100);

		$arrGenerateImageSizes	= [
			// Landscape
			[300, 150],
			// Portrait
			[300, 450],
			// Square
			[300, 300],
		];

		$arrShrinkToSquares	= [
			100,
			200,
			300,
			400,
			500,
		];

		foreach($arrGenerateImageSizes as $arrGenerateImageSize)
		{
			self::GenerateRandomImage($strSourceFilePath, $arrGenerateImageSize[0], $arrGenerateImageSize[1]);

			foreach($arrShrinkToSquares as $iSquareSize)
			{
				self::assertTrue(SimpleImage::shrink_to_square($strSourceFilePath, $strDestinationFilePath, $iSquareSize, false));
				self::assertEquals([$iSquareSize, $iSquareSize], array_slice(getimagesize($strDestinationFilePath), 0, 2));

				self::assertTrue(SimpleImage::shrink_to_square($strSourceFilePath, $strDestinationFilePath, $iSquareSize, true));
				self::assertEquals([$iSquareSize, $iSquareSize], array_slice(getimagesize($strDestinationFilePath), 0, 2));
			}
		}

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testShrinkToSquareNonWhite(): void
	{
		$strSourceFilePath		= './test.png';
		$strDestinationFilePath	= './test-shrink-to-square-non-white.png';

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 50, 50);
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strSourceFilePath, 0, 0));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strSourceFilePath, 25, 25));
		self::assertTrue(SimpleImage::shrink_to_square_non_white($strSourceFilePath, $strDestinationFilePath, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strDestinationFilePath, 0, 0));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strDestinationFilePath, 25, 25));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 200, 200, 100, 100);
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strSourceFilePath, 0, 0));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strSourceFilePath, 50, 50));
		self::assertTrue(SimpleImage::shrink_to_square_non_white($strSourceFilePath, $strDestinationFilePath, 100, false));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strDestinationFilePath, 0, 0));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strDestinationFilePath, 25, 25));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testGetColorAtPosition(): void
	{
		$strSourceFilePath		= './test.png';

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 50, 50);
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strSourceFilePath, 0, 0));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strSourceFilePath, 25, 25));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strSourceFilePath, 74, 74));
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strSourceFilePath, 99, 99));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 200, 200, 100, 100);
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strSourceFilePath, 0, 0));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strSourceFilePath, 50, 50));
		self::assertEquals('#000000', SimpleImage::get_color_at_position($strSourceFilePath, 149, 149));
		self::assertEquals('#FFFFFF', SimpleImage::get_color_at_position($strSourceFilePath, 199, 199));

		self::GenerateRandomImage($strSourceFilePath, 100, 100);
		$rImage	= self::LoadImage($strSourceFilePath);

		$iColor		= imagecolorat($rImage, 0, 0);
		$strColor	= '#'.sprintf('%06X', $iColor);
		self::assertEquals($strColor, SimpleImage::get_color_at_position($strSourceFilePath, 0, 0));
		$iColor		= imagecolorat($rImage, 50, 50);
		$strColor	= '#'.sprintf('%06X', $iColor);
		self::assertEquals($strColor, SimpleImage::get_color_at_position($strSourceFilePath, 50, 50));
		$iColor		= imagecolorat($rImage, 99, 99);
		$strColor	= '#'.sprintf('%06X', $iColor);
		self::assertEquals($strColor, SimpleImage::get_color_at_position($strSourceFilePath, 99, 99));

		unlink($strSourceFilePath);
	}

	public function testShrinkToNonWhite(): void
	{
		// Note: JPG is to "dirty" for testing shrink to white (JPG compression sometimes generates non-white pixels)
		$strSourceFilePath		= './test.png';

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 100, 100);
		self::assertEquals([100, 100], array_slice(getimagesize($strSourceFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_non_white($strSourceFilePath));
		self::assertEquals([100, 100], array_slice(getimagesize($strSourceFilePath), 0, 2));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 50, 50);
		self::assertEquals([100, 100], array_slice(getimagesize($strSourceFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_non_white($strSourceFilePath));
		self::assertEquals([50, 50], array_slice(getimagesize($strSourceFilePath), 0, 2));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 100, 50);
		self::assertEquals([100, 100], array_slice(getimagesize($strSourceFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_non_white($strSourceFilePath));
		self::assertEquals([100, 50], array_slice(getimagesize($strSourceFilePath), 0, 2));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 50, 100);
		self::assertEquals([100, 100], array_slice(getimagesize($strSourceFilePath), 0, 2));
		self::assertTrue(SimpleImage::shrink_to_non_white($strSourceFilePath));
		self::assertEquals([50, 100], array_slice(getimagesize($strSourceFilePath), 0, 2));

		unlink($strSourceFilePath);
	}

	public function testImageTrimBox(): void
	{
		// Note: JPG is to "dirty" for testing shrink to white (JPG compression sometimes generates non-white pixels)
		$strSourceFilePath		= './test.png';
		$strDestinationFilePath	= './test-image-trim-box.png';

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 100, 100);
		$arrExpected	= [
			'#'  => 2,
			'l'  => 0,
			't'  => 0,
			'r'  => 100,
			'b'  => 100,
			'w'  => 100,
			'h'  => 100,
			'w1' => 100,
			'h1' => 100,
		];
		$arrActual		= SimpleImage::imageTrimBox(self::LoadImage($strSourceFilePath));
		self::assertEquals($arrExpected, $arrActual);
		copy($strSourceFilePath, $strDestinationFilePath);
		self::assertTrue(SimpleImage::shrink_to_non_white($strDestinationFilePath));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 100, 50);
		$arrExpected	= [
			'#'  => 1,
			'l'  => 0,
			't'  => 25,
			'r'  => 100,
			'b'  => 75,
			'w'  => 100,
			'h'  => 50,
			'w1' => 100,
			'h1' => 100,
		];
		$arrActual		= SimpleImage::imageTrimBox(self::LoadImage($strSourceFilePath));
		self::assertEquals($arrExpected, $arrActual);
		copy($strSourceFilePath, $strDestinationFilePath);
		self::assertTrue(SimpleImage::shrink_to_non_white($strDestinationFilePath));
		self::assertEquals([100, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 50, 100);
		$arrExpected	= [
			'#'  => 1,
			'l'  => 25,
			't'  => 0,
			'r'  => 75,
			'b'  => 100,
			'w'  => 50,
			'h'  => 100,
			'w1' => 100,
			'h1' => 100,
		];
		$arrActual		= SimpleImage::imageTrimBox(self::LoadImage($strSourceFilePath));
		self::assertEquals($arrExpected, $arrActual);
		copy($strSourceFilePath, $strDestinationFilePath);
		self::assertTrue(SimpleImage::shrink_to_non_white($strDestinationFilePath));
		self::assertEquals([50, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImageWithBlackCenter($strSourceFilePath, 100, 100, 50, 50);
		$arrExpected	= [
			'#'  => 1,
			'l'  => 25,
			't'  => 25,
			'r'  => 75,
			'b'  => 75,
			'w'  => 50,
			'h'  => 50,
			'w1' => 100,
			'h1' => 100,
		];
		$arrActual		= SimpleImage::imageTrimBox(self::LoadImage($strSourceFilePath));
		self::assertEquals($arrExpected, $arrActual);
		copy($strSourceFilePath, $strDestinationFilePath);
		self::assertTrue(SimpleImage::shrink_to_non_white($strDestinationFilePath));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testCrop(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-crop.jpg';

		self::GenerateWhiteImage($strSourceFilePath, 100, 100);
		self::assertTrue(SimpleImage::crop($strSourceFilePath, $strDestinationFilePath, 0, 0, 100, 100));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 100, 100);
		self::assertTrue(SimpleImage::crop($strSourceFilePath, $strDestinationFilePath, 0, 0, 50, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 100, 100);
		self::assertTrue(SimpleImage::crop($strSourceFilePath, $strDestinationFilePath, 0, 0, 100, 100, 50, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 100, 100);
		self::assertTrue(SimpleImage::crop($strSourceFilePath, $strDestinationFilePath, 0, 0, 50, 50, 50, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testSquareCrop(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-square-crop.jpg';

		self::GenerateWhiteImage($strSourceFilePath, 100, 100);
		self::assertTrue(SimpleImage::square_crop($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 100, 150);
		self::assertTrue(SimpleImage::square_crop($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 150, 100);
		self::assertTrue(SimpleImage::square_crop($strSourceFilePath, $strDestinationFilePath));
		self::assertEquals([100, 100], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 100, 100);
		self::assertTrue(SimpleImage::square_crop($strSourceFilePath, $strDestinationFilePath, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 100, 150);
		self::assertTrue(SimpleImage::square_crop($strSourceFilePath, $strDestinationFilePath, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		self::GenerateWhiteImage($strSourceFilePath, 150, 100);
		self::assertTrue(SimpleImage::square_crop($strSourceFilePath, $strDestinationFilePath, 50));
		self::assertEquals([50, 50], array_slice(getimagesize($strDestinationFilePath), 0, 2));

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}

	public function testWatermark(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-watermark.jpg';
		$strWatermarkFilePath	= './watermark.png';
		self::GenerateRandomImage($strSourceFilePath, 100, 100);
		self::GenerateRandomImage($strWatermarkFilePath, 50, 50);

		$arrPositions	= [
			'top-left',
			'top',
			'top-right',
			'center-left',
			'center',
			'center-right',
			'bottom-left',
			'bottom',
			'bottom-right',
		];
		foreach($arrPositions as $strPosition)
		{
			self::assertTrue(SimpleImage::watermark($strSourceFilePath, $strDestinationFilePath, $strWatermarkFilePath, $strPosition));
			self::assertFileNotEquals($strSourceFilePath, $strDestinationFilePath);
		}

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
		unlink($strWatermarkFilePath);
	}

	public function testText(): void
	{
		$strSourceFilePath		= './test.jpg';
		$strDestinationFilePath	= './test-text.jpg';
		$strText				= 'test';
		$strFontFile			= dirname(__FILE__).'/fonts/arial.ttf';
		$iFontSize				= 12;
		$strFontColor			= '#000000';
		self::GenerateWhiteImage($strSourceFilePath, 100, 100);

		$arrPositions	= [
			'top-left',
			'top',
			'top-right',
			'center-left',
			'center',
			'center-right',
			'bottom-left',
			'bottom',
			'bottom-right',
		];
		foreach($arrPositions as $strPosition)
		{
			self::assertTrue(SimpleImage::text($strSourceFilePath, $strDestinationFilePath, $strText, $strFontFile, $iFontSize, $strFontColor, $strPosition));
			self::assertFileNotEquals($strSourceFilePath, $strDestinationFilePath);
		}

		unlink($strSourceFilePath);
		unlink($strDestinationFilePath);
	}
}
