<?php

namespace wcf\acp\page;

use wcf\data\object\type\ObjectTypeCache;
use wcf\page\AbstractPage;
use wcf\system\exception\PermissionDeniedException;

/**
 * Shows an attachment.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class AttachmentPage extends \wcf\page\AttachmentPage
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.attachment.canManageAttachment'];

    /**
     * @inheritDoc
     */
    public function checkPermissions()
    {
        AbstractPage::checkPermissions();

        if ($this->attachment->tmpHash) {
            throw new PermissionDeniedException();
        }

        // check private status of attachment's object type
        $objectType = ObjectTypeCache::getInstance()->getObjectType($this->attachment->objectTypeID);
        if ($objectType->private) {
            throw new PermissionDeniedException();
        }
    }
}
