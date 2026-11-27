<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OffersBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
