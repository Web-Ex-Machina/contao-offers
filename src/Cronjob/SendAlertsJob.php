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

namespace WEM\JobOffersBundle\Cronjob;

class SendAlertsJob
{
    /**
     * Retrieve and send all the new job offers matching user alerts.
     */
    public function do(): void
    {
        // Step 1 - Retrieve all the alerts


        // Step 2 - Retrieve all the new job offers
        // They must be newer than the last execution of this cronjob
        

        // Step 3 - Loop on alerts and job offers to gather data


        // Step 4 - Loop on the data gathered and send alerts
        // Put the parsed html for jobs into some sort of cache for the next emails

        // Step 5 - Log the results (how many alerts sents & how job offers sent)
        
    }
}
