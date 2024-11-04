<?php

declare(strict_types=1);

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
        return 'image/' === substr($mime, 0, 6) || 'application/pdf' === $mime;
    }
}
