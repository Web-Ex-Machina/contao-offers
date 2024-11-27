<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-job-offers
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\DataContainer;
use WEM\OffersBundle\Model\Alert;
use WEM\OffersBundle\Model\AlertCondition;
use Contao\Backend;

class OfferAlertConditionContainer extends Backend
{
    public function __construct()
    {
        Parent::__construct();
    }

    /**
     * Design each row of the DCA.
     */
    public function listItems(array $row): string
    {
        return sprintf(
            '%s  = %s',
            $row['field'],
            $row['value']
        );
    }
}
