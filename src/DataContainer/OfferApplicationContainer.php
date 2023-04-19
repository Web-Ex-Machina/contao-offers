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

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Image;
use Contao\Message;
use Contao\StringUtil;
use NotificationCenter\Model\Notification;
use NotificationCenter\Model\Language;
use NotificationCenter\MessageDraft\EmailMessageDraft;
use WEM\OffersBundle\Model\Application;
use WEM\OffersBundle\Model\Offer;
use WEM\OffersBundle\Model\OfferFeed;

class OfferApplicationContainer extends Backend
{
    /**
     * Design each row of the DCA.
     *
     * @return string
     */
    public function listItems($row)
    {
        $objItem = Application::findByPk($row['id']);

        return sprintf(
            '(%s) %s <span style="color:#888">[%s - %s]</span>',
            $GLOBALS['TL_LANG']['tl_wem_offer_application']['status'][$row['status']],
            $objItem->firstname.' '.$objItem->lastname,
            $objItem->city,
            $GLOBALS['TL_LANG']['CNT'][strtolower($objItem->country)]
        );
    }

    public function showCv(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if (!$row['cv']) {
            return '';
        }
        $objFile = FilesModel::findByUUID($row['cv']);
        if (!$objFile) {
            return '';
        }
        return '<a href="contao/popup.php?src=' . base64_encode($objFile->path) . '" onclick="Backend.openModalIframe({\'width\':768,\'title\':\''.StringUtil::specialchars($title).'\',\'url\':this.href});return false"; title="'.$label.'">'. Image::getHtml($icon, $label).'</a>';
    }

    public function showApplicationLetter(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        if (!$row['applicationLetter']) {
            return '';
        }
        $objFile = FilesModel::findByUUID($row['applicationLetter']);
        if (!$objFile) {
            return '';
        }
        return '<a href="contao/popup.php?src=' . base64_encode($objFile->path) . '" onclick="Backend.openModalIframe({\'width\':768,\'title\':\''.StringUtil::specialchars($title).'\',\'url\':this.href});return false"; title="'.$label.'">'. Image::getHtml($icon, $label).'</a>';
    }

    public function sendNotificationToApplication(DataContainer $dc)
    {
        // Retrieve available notifications
        $arrNotifications = $this->getAnswersNotificationChoices();
        $strPreview = '';

        // Send an error if there is no available notifications
        if (empty($arrNotifications)) {
            Message::addError($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['noAnswerNotificationsAvailable']);
        }

        // Format options
        $arrOptions = [];
        $arrOptions[] = '<option value="">-</option>';
        foreach ($arrNotifications as $id => $title) {
            $arrOptions[] = sprintf('<option value="%s"%s>%s</option>', $id, $id === (int) \Input::post('notification') ? ' selected' : '', $title);
        }

        // Catch preview
        if ('tl_wem_offers_send_answer_to_application' === \Input::post('FORM_SUBMIT')) {
            // Retrieve chosen notification
            if (!\Input::post('notification')) {
                Message::addError($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['noAnswerNotificationSelected']);
                $this->reload();
            }

            $objNotification = Notification::findByPk(\Input::post('notification'));
            $objApplication = Application::findByPk($dc->id);
            $objOffer = Offer::findByPk($objApplication->pid);
            $objFeed = OfferFeed::findByPk($objOffer->pid);

            // Format notification tokens
            $arrTokens = [];
            $arrTokens['admin_email'] = \Config::get('adminEmail');
            foreach ($objApplication->row() as $c => $v) {
                $arrTokens['recipient_'.$c] = $v;
            }
            foreach ($objOffer->row() as $c => $v) {
                $arrTokens['offer_'.$c] = $v;
            }
            foreach ($objFeed->row() as $c => $v) {
                $arrTokens['feed_'.$c] = $v;
            }

            if ("1" === \Input::post('preview')) {
                $objMessages = $objNotification->getMessages();

                if (!$objMessages) {
                    Message::addError($GLOBALS['TL_LANG']['WEM']['OFFERS']['ERROR']['noMessagesFoundInNotification']);
                    $this->reload();
                }

                while ($objMessages->next()) {
                    $objLanguage = Language::findByMessageAndLanguageOrFallback($objMessages->current(), $GLOBALS['TL_LANGUAGE']);

                    $objDraft = new EmailMessageDraft($objMessages->current(), $objLanguage, $arrTokens);
                }

                $strPreview .= '<br /><strong>'.$objDraft->getSubject().'</strong><br /><br />';
                $strPreview .= '<iframe id="emailPreview" width="100%" height="400px"></iframe>';
                $strPreview .= "<script>document.getElementById('emailPreview').contentWindow.document.write('".$this->sanitize_output($objDraft->getHtmlBody())."')</script>";
            }

            if ("1" === \Input::post('send')) {
                $objNotification->send($arrTokens);

                Message::addConfirmation($GLOBALS['TL_LANG']['WEM']['OFFERS']['MSG']['answerSent']);
                $this->reload();
            }
        }

        return Message::generate() . '
            <div id="tl_buttons">
            <a href="' . StringUtil::ampersand(str_replace('&key=sendNotificationToApplication', '', Environment::get('request'))) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
            </div>
            <form id="tl_wem_offers_send_answer_to_application" class="tl_form tl_edit_form" method="post">
            <div class="tl_formbody_edit">
                <input type="hidden" name="FORM_SUBMIT" value="tl_wem_offers_send_answer_to_application">
                <input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '">

                <div class="tl_tbox cf">
                    <div class="w50 widget">
                      <h3><label for="ctrl_notification">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['answerNotification'][0] . '</label></h3>
                      <select name="notification" id="ctrl_notification" class="tl_select tl_chosen" onfocus="Backend.getScrollOffset()">' . implode('', $arrOptions) . '</select>' . (($GLOBALS['TL_LANG']['WEM']['OFFERS']['answerNotification'][1] && \Config::get('showHelp')) ? '
                      <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['answerNotification'][1] . '</p>' : '') . '
                    </div>
                </div>

                <div class="tl_preview">
                    '.$strPreview.'
                </div>
            </div>

            <div class="tl_formbody_submit">

            <div class="tl_submit_container">
              <button type="submit" name="preview" id="preview" value="1" class="tl_submit" accesskey="p">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['BTN']['previewAnswer'] . '</button>
              <button type="submit" name="send" id="send" value="1" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['WEM']['OFFERS']['BTN']['sendAnswer'] . '</button>
            </div>

            </div>
            </form>'
        ;
    }

    public function sanitize_output($buffer)
    {
        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }

    /**
     * Get Notification Choices for this kind of modules.
     *
     * @return [Array]
     */
    public function getAnswersNotificationChoices()
    {
        $objNotifications = Database::getInstance()->execute("SELECT id,title FROM tl_nc_notification WHERE type='wem_offers_answer_to_application' ORDER BY title");

        if (!$objNotifications) {
            return [];
        }

        $arrChoices = [];
        while ($objNotifications->next()) {
            $arrChoices[$objNotifications->id] = $objNotifications->title;
        }

        return $arrChoices;
    }
}
