<?php

declare(strict_types=1);

/**
 * Contao Job Offers for Contao Open Source CMS
 * Copyright (c) 2018-2020 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-job-offers/
 */

namespace WEM\JobOffersBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use WEM\JobOffersBundle\Model\Job;
use WEM\JobOffersBundle\Model\JobFeedAttribute;

class JobContainer extends \Backend
{
    /**
     * Format items list.
     *
     * @param array $r
     *
     * @return string
     */
    public function listItems($r)
    {
        return sprintf(
            '%s <span style="color:#888">[%s]</span>',
            $r['title'],
            $r['code']
        );
    }

    /**
     * Return the "toggle visibility" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (!is_null(\Input::get('tid')) && \strlen(\Input::get('tid'))) {
            $this->toggleVisibility(\Input::get('tid'), (1 === \Input::get('state')), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.\StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Disable/enable a job.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
     */
    public function toggleVisibility($intId, $blnVisible, \DataContainer $dc = null): void
    {
        // Set the ID and action
        \Input::setGet('id', $intId);
        \Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId; // see #8043
        }

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_job']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Set the current record
        if ($dc) {
            $objRow = $this->Database->prepare('SELECT * FROM tl_wem_job WHERE id=?')
                                     ->limit(1)
                                     ->execute($intId)
            ;

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new \Versions('tl_wem_job', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_job']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->Database->prepare("UPDATE tl_wem_job SET tstamp=$time, published='".($blnVisible ? '1' : '')."' WHERE id=?")
                       ->execute($intId)
        ;

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_job']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_job']['config']['onsubmit_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
    }

    public function updatePalettes($dc)
    {
        if ($dc->id && 'edit' == \Input::get('act')) {
            $objJob = Job::findByPk($dc->id);
            $objAttributes = JobFeedAttribute::findItems(['pid' => $objJob->pid]);

            if (!$objAttributes || 0 == $objAttributes->count()) {
                return;
            }

            $objPalette = PaletteManipulator::create();
            while ($objAttributes->next()) {
                $objPalette->addField($objAttributes->name, $objAttributes->insertAfter);
            }
            $objPalette->applyToPalette('default', 'tl_wem_job');
        }
    }
}
