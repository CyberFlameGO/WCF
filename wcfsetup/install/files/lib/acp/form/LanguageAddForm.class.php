<?php

namespace wcf\acp\form;

use wcf\data\language\Language;
use wcf\data\language\LanguageEditor;
use wcf\form\AbstractForm;
use wcf\system\exception\UserInputException;
use wcf\system\language\LanguageFactory;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Shows the language add form.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class LanguageAddForm extends AbstractForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'wcf.acp.menu.link.language.list';

    /**
     * country code
     * @var string
     */
    public $countryCode = '';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.language.canManageLanguage'];

    /**
     * language object
     * @var Language
     */
    public $language;

    /**
     * language name
     * @var string
     */
    public $languageName = '';

    /**
     * language code
     * @var string
     */
    public $languageCode = '';

    /**
     * list of available languages
     * @var Language[]
     */
    public $languages = [];

    /**
     * source language object
     * @var Language
     */
    public $sourceLanguage;

    /**
     * source language id
     * @var int
     */
    public $sourceLanguageID = 0;

    /**
     * @var string[]
     */
    public array $locales;

    public string $locale = '';

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        $locales = \ResourceBundle::getLocales('');
        if ($locales === false) {
            throw new \RuntimeException('Unable to query the ICU database to retrieve the available locales.');
        }

        $displayLocale = WCF::getLanguage()->getLocale();
        foreach ($locales as $locale) {
            $this->locales[$locale] = \Locale::getDisplayName($locale, $displayLocale);
        }

        $collator = new \Collator($displayLocale);
        $collator->asort($this->locales, \Collator::SORT_STRING);
    }

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        parent::readFormParameters();

        if (isset($_POST['countryCode'])) {
            $this->countryCode = StringUtil::trim($_POST['countryCode']);
        }
        if (isset($_POST['languageName'])) {
            $this->languageName = StringUtil::trim($_POST['languageName']);
        }
        if (isset($_POST['languageCode'])) {
            $this->languageCode = StringUtil::trim($_POST['languageCode']);
        }
        if (isset($_POST['sourceLanguageID'])) {
            $this->sourceLanguageID = \intval($_POST['sourceLanguageID']);
        }
        if (isset($_POST['locale'])) {
            $this->locale = $_POST['locale'];
        }
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        parent::validate();

        // language name
        if (empty($this->languageName)) {
            throw new UserInputException('languageName');
        }

        // country code
        if (empty($this->countryCode)) {
            throw new UserInputException('countryCode');
        }

        // language code
        $this->validateLanguageCode();

        // source language id
        $this->validateSource();

        if (!isset($this->locales[$this->locale])) {
            throw new UserInputException('locale');
        }
    }

    /**
     * Validates the language code.
     */
    protected function validateLanguageCode()
    {
        if (empty($this->languageCode)) {
            throw new UserInputException('languageCode');
        }
        if (LanguageFactory::getInstance()->getLanguageByCode($this->languageCode)) {
            throw new UserInputException('languageCode', 'notUnique');
        }
    }

    /**
     * Validates given source language.
     */
    protected function validateSource()
    {
        if (empty($this->sourceLanguageID)) {
            throw new UserInputException('sourceLanguageID');
        }

        // get language
        $this->sourceLanguage = LanguageFactory::getInstance()->getLanguage($this->sourceLanguageID);
        if (!$this->sourceLanguage->languageID) {
            throw new UserInputException('sourceLanguageID');
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        $this->language = LanguageEditor::create(\array_merge($this->additionalFields, [
            'countryCode' => \mb_strtolower($this->countryCode),
            'languageName' => $this->languageName,
            'languageCode' => \mb_strtolower($this->languageCode),
            'locale' => $this->locale,
        ]));
        $languageEditor = new LanguageEditor($this->sourceLanguage);
        $languageEditor->copy($this->language);

        // copy content
        LanguageEditor::copyLanguageContent($this->sourceLanguage->languageID, $this->language->languageID);

        // reset caches
        LanguageFactory::getInstance()->clearCache();
        LanguageFactory::getInstance()->deleteLanguageCache();

        $this->saved();

        // show success message
        WCF::getTPL()->assign([
            'success' => true,
            'objectEditLink' => LinkHandler::getInstance()->getControllerLink(
                LanguageEditForm::class,
                ['id' => $this->language->languageID]
            ),
        ]);

        // reset values
        $this->countryCode = $this->languageCode = $this->languageName = $this->locale = '';
        $this->sourceLanguageID = 0;
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        $this->languages = LanguageFactory::getInstance()->getLanguages();
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'countryCode' => $this->countryCode,
            'languageName' => $this->languageName,
            'languageCode' => $this->languageCode,
            'sourceLanguageID' => $this->sourceLanguageID,
            'languages' => $this->languages,
            'locale' => $this->locale,
            'locales' => $this->locales,
            'action' => 'add',
        ]);
    }
}
