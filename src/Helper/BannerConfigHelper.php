<?php

namespace PromotionalBanner\Helper;

use Exception;
use PrestaShop\PrestaShop\Core\Exception\TranslatableCoreException;
use Tools;
use \Validate;

final class BannerConfigHelper
{
    //<editor-fold desc="Declaration of variables">
    
    // - Technical limits of banner images -
    private const MAX_IMG_BG_SIZE = 512_000; // 500 ko
    private const MAX_IMG_BG_PX = 4096; // Limits the maximum size of a side
    private const IMG_BG_EXT_ALLOWED_WITH_WEBP = ['jpg', 'jpeg', 'png', 'webp'];
    private const IMG_BG_EXT_ALLOWED_NO_WEBP = ['jpg', 'jpeg', 'png'];
    
    //</editor-fold>


    /**
     * Sanitize string
     * @param string $textParam
     * @param int $maxLength
     * @return string
     * @throws TranslatableCoreException
     */
    public static function getValidString(string $textParam, int $maxLength = 255): string
    {
        $text = trim($textParam);

        // Reasonable limit size - SEO / UI (255 car. max by default)
        if (Tools::strlen($text) > $maxLength) {
            throw new TranslatableCoreException('This text is too long (%maxLength% chars max).', 'Modules.Promotionalbanner.Admin', ['maxLength' => $maxLength]);
        }

        // Disallow any tags or control characters
        if (!Validate::isGenericName($text)) {
            throw new TranslatableCoreException('This text contains invalid characters.', 'Modules.Promotionalbanner.Admin', []);
        }

        // Remove tags
        $text = strip_tags($text);

        return $text;
    }


    /**
     * Sanitize the image and return the image path
     * @param array $file
     * @param string $destinationDirectory
     * @return string
     * @throws Exception
     */
    public static function getValidImg(array $file, string $destinationDirectory): string
    {
        // Get extensions allowed - Depending on whether GD supports webp
        $imgBgExtAllowed = \function_exists('\imagecreatefromwebp') ? self::IMG_BG_EXT_ALLOWED_WITH_WEBP : self::IMG_BG_EXT_ALLOWED_NO_WEBP;

        // - Basic Prestashop Control -
        if ($error = \ImageManager::validateUpload($file, self::MAX_IMG_BG_SIZE, $imgBgExtAllowed)) {
            throw new Exception($error);
        }

        // - Name of the new image -
        $extension = Tools::substr(strrchr($file['name'], '.'), 1);
        $fileName = 'banner_'.uniqid().'.'.$extension;

        // - Avoid overly large images and "image bombs" -
        try{
            self::processWithGd($file['tmp_name'], $destinationDirectory.$fileName);
        }
        catch (Exception $e){
            throw new Exception($e);
        }

        return $fileName;

    }

    /**
     * Avoid overly large images and "image bombs".
     * Processing via GD (JPEG/PNG/WEBP) - Included by default in PHP
     * @param string $src
     * @param string $dest
     * @return void
     * @throws TranslatableCoreException
     */
    private static function processWithGd(string $src, string $dest): void
    {
        $info = getimagesize($src);
        if ($info === false) {
            throw new TranslatableCoreException('Invalid image file.', 'Modules.Promotionalbanner.Admin', []);
        }
        [$w, $h, $type] = $info;

        // - Checking the dimensions -
        if ($w > self::MAX_IMG_BG_PX || $h > self::MAX_IMG_BG_PX) {
            // Delta ratio to be at the limit size
            $ratio = min(self::MAX_IMG_BG_PX / $w, self::MAX_IMG_BG_PX / $h);
            $nw    = (int) round($w * $ratio);
            $nh    = (int) round($h * $ratio);
        }
        else {
            $nw = $w;
            $nh = $h;
        }

        // - Decoding / Recoding -
        switch ($type) {

            // WEBP
            case IMAGETYPE_WEBP:
                if (!\function_exists('\imagecreatefromwebp')) { // Depending on whether GD supports webp
                    throw new TranslatableCoreException('GD was not compiled with WEBP support.', 'Modules.Promotionalbanner.Admin', []);
                }
                $srcIm  = imagecreatefromwebp($src);
                $destIm = imagecreatetruecolor($nw, $nh);
                imagealphablending($destIm, false);
                imagesavealpha($destIm, true);
                imagecopyresampled($destIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagewebp($destIm, $dest, 85); // 85 = 1 x reference
                break;

            // PNG
            case IMAGETYPE_PNG:
                $srcIm = imagecreatefrompng($src);
                $destIm = imagecreatetruecolor($nw, $nh);
                imagealphablending($destIm, false);
                imagesavealpha($destIm, true);
                imagecopyresampled($destIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagepng($destIm, $dest, 7); // 0 = no compression, 9 = max
                break;

            // DEFAULT - JPEG
            default:
                $srcIm  = imagecreatefromjpeg($src);
                $destIm = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($destIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagejpeg($destIm, $dest, 85); // 85 = 1 x reference

        }

        // Memory cleaning
        imagedestroy($srcIm);
        imagedestroy($destIm);
    }

}
