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

use NotificationCenter\Model\Notification;

use WEM\JobOffersBundle\Model\Alert;
use WEM\JobOffersBundle\Model\AlertCondition;
use WEM\JobOffersBundle\Model\Job;

class SendAlertsJob
{
    /**
     * Retrieve and send all the new job offers matching user alerts.
     *
     * Executed every hour
     */
    public function do(): void
    {
        // Log the start of the job and setup some vars
        \System::log("Cronjob SendAlertsJob started", __METHOD__, "WEMJOBOFFERS");

        $t = Alert::getTable();
        $t2 = AlertCondition::getTable();
        $t3 = Job::getTable();
        $nbAlerts = 0;
        $nbJobs = 0;
        $arrJobFeedCache = [];
        $arrJobCache = [];

        // We need to retrieve the alerts depending on their frequency
        // hourly
        // or daily and lastJob < time - 1 day
        // or weekly and lastJob < time - 1 week
        // or monthly and lastJob < time - 1 month
        $arrWhere = [];
        $arrWhere[] = sprintf(
            "(
                $t.frequency = 'hourly'
                OR ($t.frequency = 'daily' AND $t.lastJob < %s)
                OR ($t.frequency = 'weekly' AND $t.lastJob < %s)
                OR ($t.frequency = 'monthly' AND $t.lastJob < %s)
            )",
            strtotime("-1 day"),
            strtotime("-1 week"),
            strtotime("-1 month"),
        );
        $objAlerts = Alert::findItems($c);

        // Quit the job if there is no alerts to retrieve
        if(!$objAlerts || 0 == $objAlerts->count()) {
            \System::log("Nothing to send, abort !", __METHOD__, "WEMJOBOFFERS");
        }

        // Now, loop on the alerts and check if there is jobs matching its conditions
        while($objAlerts->next()) {
            // Retrieve the alert conditions
            $objConditions = AlertCondition::findItems(['pid' => $objAlerts->id]);

            // It should not happen but hey. Expect the unexpected ಠ_ಠ
            if(!$objConditions || 0 == $objConditions->count()) {
                continue;
            }

            // Retrieve the feed linked
            if(array_key_exists($objAlerts->feed, $arrJobFeedCache)) {
                $objFeed = $arrJobFeedCache[$objAlerts->feed]['model'];
            } else {
                $objFeed = $objAlerts->getRelated("feed");
                $arrJobFeedCache[$objAlerts->feed]['model'] = $objFeed;
                $arrJobFeedCache[$objAlerts->feed]['tokens'] = [];

                // Format and store tokens
                foreach($objFeed->row() as $k => $v) {
                    $arrJobFeedCache[$objAlerts->feed]['tokens']["jobfeed_".$k] = $v;
                }
            }
            

            // Setup default conditions
            $arrConditions = [];
            $arrConditions["pid"] = $objFeed->id;
            $arrConditions["published"] = 1;
            $arrConditions["where"] = [];

            // Format alert conditions for request
            // @todo > Take in consideration that we can have multiple values per field.
            // Ex : 2 cities for locations, 2 cities for country etc...
            while($objConditions->next()) {
                $arrConditions[$objConditions->field] = $objConditions->value;
            }

            // Depending on frequency, adjust job time condition
            switch ($objAlerts->frequency) {
                case 'daily':
                    $arrConditions["where"][] = sprintf("%s.postedAt > %s", $t3, strtotime("-1 day"));
                    break;
                case 'weekly':
                    $arrConditions["where"][] = sprintf("%s.postedAt > %s", $t3, strtotime("-1 week"));
                    break;
                case 'monthly':
                    $arrConditions["where"][] = sprintf("%s.postedAt > %s", $t3, strtotime("-1 month"));
                    break;
                case 'hourly':
                default:
                    $arrConditions["where"][] = sprintf("%s.postedAt > %s", $t3, strtotime("-1 hour"));
            }

            // Retrieve jobs matching the conditions
            $objJobs = Job::findItems($arrConditions);

            // Skip if no jobs were found
            if(!$objJobs || 0 == $objJobs->count()) {
                continue;
            }

            // Prepare some tokens for notification
            $arrTokens = [];
            $arrJobBuffer = [];

            foreach ($objAlerts->row() as $k => $v) {
                $arrTokens["recipient_".$k] = $v;
            }

            // Loop on the jobs, format everything and send the notification \o/
            while($objJobs->next()) {
                if(array_key_exists($objJobs->id, $arrJobCache)) {
                    $arrJobBuffer[] = $arrJobCache[$objJobs->id];
                } else {
                    $arrJobBuffer[] = $this->parseJob($objJobs->current(), $objFeed->tplAlertJob);
                }

                $nbJobs++;
            }

            $arrTokens['jobshtml'] = implode('', $arrJobBuffer);
            $arrTokens['jobstext'] = strip_tags($arrTokens['jobshtml']);

            if($objNotification = Notification::findByPk($objFeed->ncEmailAlert)) {
                $nbAlerts++;
                $objNotification->send($arrTokens);
            }
        }

        // Step 5 - Log the results (how many alerts sents & how job offers sent)
        \System::log(sprintf("Cronjob done, %s alerts and %s jobs sent", $nbAlerts, $nbJobs), __METHOD__, "WEMJOBOFFERS");
    }

    /**
     * Format a job block for the notification
     * 
     * @param  WEM\JobOffersBundle\Model\Job $objJob
     * @param  string $strTemplate
     * 
     * @return String
     */
    protected function parseJob($objJob, $strTemplate = 'job_alert_default') {
        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->setData($objJob->row());

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['parseArticles']) && \is_array($GLOBALS['TL_HOOKS']['parseArticles']))
        {
            foreach ($GLOBALS['TL_HOOKS']['parseArticles'] as $callback)
            {
                $this->import($callback[0]);
                $this->{$callback[0]}->{$callback[1]}($objTemplate, $objJob->row(), $this);
            }
        }

        return $objTemplate->parse();
    }
}
