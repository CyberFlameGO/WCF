<?php

namespace wcf\data\language;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IToggleAction;
use wcf\data\TDatabaseObjectToggle;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Executes language-related actions.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method  Language        create()
 * @method  LanguageEditor[]    getObjects()
 * @method  LanguageEditor      getSingleObject()
 */
class LanguageAction extends AbstractDatabaseObjectAction implements IToggleAction
{
    use TDatabaseObjectToggle;

    /**
     * @inheritDoc
     */
    protected $className = LanguageEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.language.canManageLanguage'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.language.canManageLanguage'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.language.canManageLanguage'];

    /**
     * language editor object
     * @var LanguageEditor
     */
    protected $languageEditor;

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'setAsDefault', 'update'];

    /**
     * Validates permission to set a language as default.
     */
    public function validateSetAsDefault()
    {
        WCF::getSession()->checkPermissions($this->permissionsUpdate);

        $this->languageEditor = $this->getSingleObject();
    }

    /**
     * Sets language as default
     */
    public function setAsDefault()
    {
        $this->languageEditor->setAsDefault();

        if ($this->languageEditor->getDecoratedObject()->isDisabled) {
            $this->languageEditor->update(['isDisabled' => 0]);
        }
    }

    /**
     * @inheritDoc
     */
    public function validateToggle()
    {
        parent::validateUpdate();

        foreach ($this->getObjects() as $language) {
            if ($language->isDefault) {
                throw new UserInputException('objectIDs');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function validateDelete()
    {
        parent::validateDelete();

        foreach ($this->getObjects() as $language) {
            if (!$language->isDeletable()) {
                throw new UserInputException('objectIDs');
            }
        }
    }
}
