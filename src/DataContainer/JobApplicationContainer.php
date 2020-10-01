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

namespace WEM\JobOffersBundle\DataContainer;

class JobApplicationContainer
{
    /**
     * Design each row of the DCA.
     *
     * @return string
     */
    public function listItems($row)
    {
        return sprintf(
            '(%s) %s <span style="color:#888">[%s - %s]</span>',
            $GLOBALS['TL_LANG']['tl_wem_job_application']['status'][$row['status']],
            $row['firstname'].' '.$row['lastname'],
            $row['city'],
            $GLOBALS['TL_LANG']['CNT'][$row['country']]
        );
    }
}
