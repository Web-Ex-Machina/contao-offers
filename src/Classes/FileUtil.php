<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\Classes;

use Contao\File;

class FileUtil
{
    /**
     * Check if a file is displaybale in browser.
     *
     * @param File $objFile The file to check
     */
    public static function isDisplayableInBrowser(File $objFile): bool
    {
        $mime = strtolower($objFile->mime);
        return str_starts_with($mime, 'image/') || $mime === 'application/pdf';
    }
}
