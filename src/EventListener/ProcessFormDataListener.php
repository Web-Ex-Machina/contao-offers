<?php

declare(strict_types=1);

/**
 * Personal Data Manager for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-smartgear
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/personal-data-manager/
 */

namespace WEM\OffersBundle\EventListener;

use Exception;
use Contao\Form;
use Psr\Log\LoggerInterface;
use WEM\OffersBundle\Model\Application;
use WEM\UtilsBundle\Classes\Encryption;

class ProcessFormDataListener
{
    private LoggerInterface $logger;

    protected Encryption $encryption;

    public function __construct(LoggerInterface $logger,Encryption $encryption)
    {
        $this->encryption = $encryption;
        $this->logger = $logger;
    }

    public function __invoke(
        array $submittedData,
        array $formData,
        ?array $files,
        array $labels,
        Form $form
    ): void {
        try {
            if ('offer-application' === $form->formID) {
                // get the last submitted application
                $objApplication = Application::findItems([], 1, 0, ['order' => 'tstamp DESC']);
                if ($objApplication) {
                    $objApplication = $objApplication->next()->current();
                    $fieldsManagedByPdm = (new Application($this->encryption))->getPersonalDataFieldsNames();
                    foreach ($fieldsManagedByPdm as $field) {
                        $objApplication->markModified($field);
                    }

                    $objApplication->save();
                }
            }
        } catch (Exception $exception) {
            $this->logger->log('ERROR',vsprintf(($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['generic'])?:"coucou", [$exception->getMessage(), $exception->getTrace()]),["WEM_OFFERS"]);
        }
    }
}
