<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Psr\Log\LoggerInterface;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeedAttribute;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Add the feed attributes as fields of the offer DCA.
 */
#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $strTable): void
    {
        if (Offer::getTable() !== $strTable) {
            return;
        }

        try {
            foreach (OfferFeedAttribute::findAll() ?? [] as $objAttribute) {
                $GLOBALS['TL_DCA'][$strTable]['fields'][$objAttribute->name] = $this->parseDcaAttribute($objAttribute->row());
            }
        } catch (\Exception $exception) {
            // The table might not exist yet (e.g. during the installation)
            $this->logger->error(\sprintf('Could not load the offer attributes: %s', $exception->getMessage()), [
                'exception' => $exception,
            ]);
        }
    }

    protected function parseDcaAttribute(array $row): array
    {
        // Generic data
        $data = [
            'label' => [
                0 => $row['label'] ?: $row['name'],
            ],
            'name' => $row['name'],
            'inputType' => $row['type'],
            'eval' => [],
            'sql' => [
                'name' => $row['name'],
            ],
        ];

        // Maxlength settings
        if ($row['maxlength']) {
            $data['eval']['maxlength'] = (int) $row['maxlength'];
            $data['sql']['length'] = (int) $row['maxlength'];
        }

        // Available for alerts settings
        // if ($row['wemoffers_isAvailableForAlerts']) {
        if ($row['isAlertCondition']) {
            $data['eval']['isAlertCondition'] = true;
        }

        // Available for filters settings
        // if ($row['wemoffers_isAvailableForFilters']) {
        if ($row['isFilter']) {
            $data['eval']['isFilter'] = true;
        }

        // Mandatory settings
        if ($row['mandatory']) {
            $data['eval']['mandatory'] = true;
        }

        // rte settings
        if ($row['rte']) {
            $data['eval']['rte'] = $row['rte'];
        }

        if ($row['explanation']) {
            $data['explanation'] = $row['explanation'];
        }

        if ($row['class']) {
            $data['eval']['tl_class'] = $row['class'];
        }

        // Allow helpwizard
        if ($row['helpwizard']) {
            $data['eval']['helpwizard'] = true;
        }

        switch ($row['type']) {
            case 'text':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                $data['sql']['type'] = 'string';
                $data['sql']['length'] ??= 255;

                if ($row['value']) {
                    $data['default'] = $row['value'];
                    $data['sql']['default'] = $row['value'];
                } else {
                    $data['default'] = '';
                    $data['sql']['default'] = '';
                }

                break;

            case 'textarea':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                $data['sql'] = 'mediumtext NULL';

                break;

            case 'select':
                $data['sql']['type'] = 'string';
                $data['sql']['length'] = 255;
                $data['default'] = '';
                $data['sql']['default'] = '';

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql'] = 'blob NULL';
                }

                // Chosen settings
                if ($row['chosen']) {
                    $data['eval']['chosen'] = true;
                }

                // Options
                $options = StringUtil::deserialize($row['options']);
                if ($options !== null) {
                    $data['options'] = [];
                    $blnIsGroup = false;
                    $blnIsChild = true;
                    $key = null;
                    foreach ($options as $o) {
                        if (\array_key_exists('group', $o) && $o['group']) {
                            $blnIsGroup = true;
                            $blnIsChild = false;
                            $key = $o['label'];
                        } else {
                            $blnIsGroup = false;
                            $blnIsChild = true;
                        }

                        if ($key === null) {
                            $data['options'][$o['value']] = $o['label'];
                        } elseif ($blnIsGroup) {
                            // $data['options'][$key] = ['label'=>$o['label'],'options'=>[]];
                        } elseif ($blnIsChild) {
                            $data['options'][$key][$o['value']] = $o['label'];
                        }

                        if (! empty($o['default']) && \is_array($data['sql'])) {
                            $data['default'] = $o['value'];
                            $data['sql']['default'] = $o['value'];
                        }
                    }
                }

                break;

            case 'picker':
                // Fkey settings
                if ($row['fkey']) {
                    $data['foreignKey'] = $row['fkey'];
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql'] = 'blob NULL';
                    $data['relation'] = [
                        'type' => 'hasMany',
                        'load' => 'lazy',
                    ];
                } else {
                    $data['sql'] = 'int(10) unsigned NOT NULL default 0';
                    $data['relation'] = [
                        'type' => 'hasOne',
                        'load' => 'lazy',
                    ];
                }

                break;

            case 'fileTree':
                // filesOnly settings
                if ($row['filesOnly']) {
                    $data['eval']['filesOnly'] = true;
                }

                // extensions settings
                if ($row['fieldType']) {
                    $data['eval']['fieldType'] = $row['fieldType'];
                }

                // extensions settings
                if ($row['extensions']) {
                    $data['eval']['extensions'] = $row['extensions'];
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                    $data['sql'] = 'blob NULL';
                } else {
                    $data['sql'] = 'binary(16) NULL';
                }

                break;

            case 'listWizard':
                // Allow HTML settings
                if ($row['allowHtml']) {
                    $data['eval']['allowHtml'] = true;
                }

                // Multiple settings
                if ($row['multiple']) {
                    $data['eval']['multiple'] = true;
                }

                $data['sql'] = 'blob NULL';
                break;
        }

        return $data;
    }
}
