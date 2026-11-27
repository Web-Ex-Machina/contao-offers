<?php

declare(strict_types=1);

/**
 * Contao Offers Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 * @see        https://github.com/Web-Ex-Machina/contao-offers
 * @license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt
 */

namespace WEM\OffersBundle\DataContainer;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\Message;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\LanguageConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\TokenCollectionStamp;
use WEM\OffersBundle\Model\Application;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeed;
use WEM\OffersBundle\NotificationType\AnswerToApplicationNotificationType;

class OfferApplicationContainer
{
    public function __construct(
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly NotificationCenter $notificationCenter,
        private readonly SimpleTokenParser $simpleTokenParser,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * Design each row of the DCA.
     */
    #[AsCallback(table: 'tl_wem_offer_application', target: 'list.sorting.child_record')]
    public function listItems(array $row): string
    {
        $objItem = Application::findById($row['id']);

        return \sprintf(
            '(%s) %s <span class="label-info">[%s - %s]</span>',
            $GLOBALS['TL_LANG']['tl_wem_offer_application']['status'][$row['status']] ?? $row['status'],
            $objItem?->firstname . ' ' . $objItem?->lastname,
            $objItem?->city,
            $GLOBALS['TL_LANG']['CNT'][strtolower((string) $objItem?->country)] ?? $objItem?->country,
        );
    }

    #[AsCallback(table: 'tl_wem_offer_application', target: 'list.operations.show_cv.button')]
    public function showCv(DataContainerOperation $operation): void
    {
        $this->setFilePopupUrl($operation, 'cv');
    }

    #[AsCallback(table: 'tl_wem_offer_application', target: 'list.operations.show_applicationLetter.button')]
    public function showApplicationLetter(DataContainerOperation $operation): void
    {
        $this->setFilePopupUrl($operation, 'applicationLetter');
    }

    /**
     * Back end key callback: preview and send an answer to an application.
     */
    public function sendNotificationToApplication(DataContainer $dc): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $arrNotifications = $this->notificationCenter->getNotificationsForNotificationType(AnswerToApplicationNotificationType::NAME);
        $notificationId = (int) $request?->request->get('notification');
        $strPreview = '';

        // Send an error if there is no available notifications
        if ($arrNotifications === []) {
            Message::addError($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['noAnswerNotificationsAvailable']);
        }

        // Format options
        $arrOptions = ['<option value="">-</option>'];

        foreach ($arrNotifications as $id => $title) {
            $arrOptions[] = \sprintf('<option value="%s"%s>%s</option>', $id, $id === $notificationId ? ' selected' : '', StringUtil::specialchars($title));
        }

        if ($request?->request->get('FORM_SUBMIT') === 'tl_wem_offers_send_answer_to_application') {
            if ($notificationId === 0) {
                Message::addError($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['noAnswerNotificationSelected']);
                Controller::reload();
            }

            $arrTokens = $this->getApplicationTokens((int) $dc->id);

            if ($request->request->get('preview') === '1') {
                $strPreview = $this->getPreview($notificationId, $arrTokens);
            }

            if ($request->request->get('send') === '1') {
                $this->notificationCenter->sendNotification($notificationId, $arrTokens);

                Message::addConfirmation($GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['answerSent']);
                Controller::reload();
            }
        }

        $backUrl = preg_replace('/&(amp;)?key=sendNotificationToApplication/', '', (string) $request?->getRequestUri());
        $help = ($GLOBALS['TL_LANG']['WEM']['OFFERS']['answerNotification'][1] ?? '') && Config::get('showHelp')
            ? '<p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['answerNotification'][1] . '</p>'
            : '';

        return Message::generate() . '
            <div id="tl_buttons">
              <a href="' . StringUtil::ampersand($backUrl) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
            </div>
            <form id="tl_wem_offers_send_answer_to_application" class="tl_form tl_edit_form" method="post">
              <div class="tl_formbody_edit">
                <input type="hidden" name="FORM_SUBMIT" value="tl_wem_offers_send_answer_to_application">
                <input type="hidden" name="REQUEST_TOKEN" value="' . $this->csrfTokenManager->getDefaultTokenValue() . '">
                <div class="tl_tbox cf">
                  <div class="w50 widget">
                    <h3><label for="ctrl_notification">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['answerNotification'][0] . '</label></h3>
                    <select name="notification" id="ctrl_notification" class="tl_select">' . implode('', $arrOptions) . '</select>' . $help . '
                  </div>
                </div>
                <div class="tl_preview">' . $strPreview . '</div>
              </div>
              <div class="tl_formbody_submit">
                <div class="tl_submit_container">
                  <button type="submit" name="preview" id="preview" value="1" class="tl_submit" accesskey="p">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['BTN']['previewAnswer'] . '</button>
                  <button type="submit" name="send" id="send" value="1" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['BTN']['sendAnswer'] . '</button>
                </div>
              </div>
            </form>';
    }

    private function setFilePopupUrl(DataContainerOperation $operation, string $field): void
    {
        $uuid = $operation->getRecord()[$field] ?? null;
        $objFile = $uuid ? FilesModel::findByUuid($uuid) : null;

        if (! $objFile instanceof FilesModel) {
            $operation->hide();

            return;
        }

        $operation->setUrl($this->router->generate('contao_backend_popup', [
            'src' => base64_encode($objFile->path),
        ]));
    }

    private function getApplicationTokens(int $applicationId): array
    {
        $objApplication = Application::findById($applicationId);
        $objOffer = Offer::findById($objApplication?->pid);
        $objFeed = OfferFeed::findById($objOffer?->pid);

        $arrTokens = [
            'admin_email' => Config::get('adminEmail'),
            'recipient_email' => $objApplication?->email,
        ];

        foreach ([
            'recipient_' => $objApplication,
            'offer_' => $objOffer,
            'feed_' => $objFeed,
        ] as $prefix => $objModel) {
            foreach ($objModel?->row() ?? [] as $c => $v) {
                $arrTokens[$prefix . $c] = $v;
            }
        }

        return $arrTokens;
    }

    /**
     * Render the subject and HTML body of the notification messages.
     */
    private function getPreview(int $notificationId, array $arrTokens): string
    {
        $stamps = $this->notificationCenter->createBasicStampsForNotification($notificationId, $arrTokens, $this->requestStack->getCurrentRequest()?->getLocale());
        $strPreview = '';

        foreach ($this->notificationCenter->createParcelsForNotification($notificationId, $stamps) as $parcel) {
            $languageConfig = $parcel->getStamp(LanguageConfigStamp::class)?->languageConfig;
            $tokens = $parcel->getStamp(TokenCollectionStamp::class)?->tokenCollection->forSimpleTokenParser() ?? [];

            if ($languageConfig === null) {
                continue;
            }

            $subject = $this->simpleTokenParser->parse($languageConfig->getString('email_subject'), $tokens);
            $html = $this->simpleTokenParser->parse($languageConfig->getString('email_html') ?: nl2br($languageConfig->getString('email_text')), $tokens);

            $strPreview .= \sprintf(
                '<br><strong>%s</strong><br><br><iframe width="100%%" height="400" sandbox srcdoc="%s"></iframe>',
                StringUtil::specialchars($subject),
                htmlspecialchars($html, ENT_QUOTES),
            );
        }

        if ($strPreview === '') {
            Message::addError($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['noMessagesFoundInNotification']);
        }

        return $strPreview;
    }
}
