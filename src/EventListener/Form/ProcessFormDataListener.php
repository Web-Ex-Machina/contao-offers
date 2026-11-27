<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\EventListener\Form;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Dbafs;
use Contao\FilesModel;
use Contao\Form;
use Contao\ModuleModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\OffersBundle\Model\Application;
use WEM\OffersBundle\Model\Offer;

/**
 * Store the applications sent through the forms used by the offers modules.
 */
#[AsHook('processFormData')]
class ProcessFormDataListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        // Only process the forms used in the offers modules and sent with an offer ID
        if (empty($submittedData['pid']) || ModuleModel::countBy('offer_applicationForm', $form->id) === 0) {
            return;
        }

        try {
            $objOffer = Offer::findById($submittedData['pid']);

            // Do not process if the offer has not been found, let the others listeners do their job
            if (! $objOffer instanceof Offer) {
                return;
            }

            $columns = array_map(
                strtolower(...),
                array_keys($this->connection->createSchemaManager()->listTableColumns(Application::getTable())),
            );
            $hasColumn = static fn (string $name): bool => \in_array(strtolower($name), $columns, true);

            $objApplication = new Application();
            $objApplication->createdAt = time();
            $objApplication->tstamp = time();

            foreach ($submittedData as $c => $v) {
                if ($hasColumn((string) $c)) {
                    $objApplication->{$c} = $v;
                }
            }

            $objApplication->save();

            foreach ($files ?? [] as $name => $file) {
                // Do not process files we cannot link to the application
                if (! $hasColumn((string) $name)) {
                    continue;
                }

                $objApplication->{$name} = $this->moveFile((string) $name, $file, $objOffer, $objApplication)->uuid;
            }

            // Apply PDM system
            foreach ($objApplication->getPersonalDataFieldsNames() as $field) {
                $objApplication->markModified($field);
            }

            $objApplication->save();

            // Clean session
            $this->requestStack->getSession()->remove('wem_offer');
        } catch (\Exception $exception) {
            $this->logger->error(\sprintf('Could not store the offer application: %s', $exception->getMessage()), [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Move file into a subfolder with a clearer name.
     *
     * Rule: files/applications/{offer_code}/{lastname_firstname}/{lastname_firstname_name}.{extension}
     */
    private function moveFile(string $name, array $file, Offer $objOffer, Application $objApplication): FilesModel
    {
        $ext = pathinfo((string) ($file['full_path'] ?? $file['name'] ?? ''), PATHINFO_EXTENSION);
        $lastname = StringUtil::prepareSlug((string) $objApplication->lastname);
        $firstname = StringUtil::prepareSlug((string) $objApplication->firstname);

        $strFolder = \sprintf('files/applications/%s/%s_%s', $objOffer->code, $lastname, $firstname);
        $strFile = \sprintf('%s/%s_%s_%s.%s', $strFolder, $lastname, $firstname, $name, $ext);

        new Filesystem()->rename((string) $file['tmp_name'], $this->projectDir . '/' . $strFile, true);

        return Dbafs::addResource($strFile);
    }
}
