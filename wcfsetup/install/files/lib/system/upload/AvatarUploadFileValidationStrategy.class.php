<?php

namespace wcf\system\upload;

use wcf\data\user\avatar\UserAvatar;
use wcf\system\exception\SystemException;

/**
 * Validation strategy for avatar uploads.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class AvatarUploadFileValidationStrategy extends DefaultUploadFileValidationStrategy
{
    /**
     * @inheritDoc
     */
    public function validate(UploadFile $uploadFile)
    {
        if (!parent::validate($uploadFile)) {
            return false;
        }

        // check image size
        try {
            $imageData = \getimagesize($uploadFile->getLocation());
            if ($imageData[0] < UserAvatar::AVATAR_SIZE || $imageData[1] < UserAvatar::AVATAR_SIZE) {
                $uploadFile->setValidationErrorType('tooSmall');

                return false;
            } else {
                switch ($imageData[2]) {
                    case \IMAGETYPE_WEBP:
                        // Reject WebP images regardless of any file extension restriction, they are
                        // neither supported in Safari nor in Internet Explorer 11. We can safely lift
                        // this restriction once Apple implements the support or if any sort of fall-
                        // back mechanism is implemented: https://github.com/WoltLab/WCF/issues/2838

                        $uploadFile->setValidationErrorType('invalidExtension');

                        return false;
                    case \IMAGETYPE_PNG:
                    case \IMAGETYPE_GIF:
                    case \IMAGETYPE_JPEG:
                        // Validate the mime type against the list of allowed extensions.
                        //
                        // We usually don't care about the extension, restricting allowed file extensions
                        // primarily exists to prevent users from uploaded clickable '.exe'. The software
                        // itself only ever uses the mime type.
                        //
                        // In the case of avatars, though, the administrator might want to disallow uploading
                        // GIF files to prevent the most common case of animated avatar, thus we specifically
                        // validate the mime type against the extension.
                        $extension = \image_type_to_extension($imageData[2], false);
                        if (!\in_array($extension, $this->fileExtensions)) {
                            $uploadFile->setValidationErrorType('invalidExtension');

                            return false;
                        }

                        break;
                }
            }
        } catch (SystemException $e) {
            if (ENABLE_DEBUG_MODE) {
                throw $e;
            }

            $uploadFile->setValidationErrorType('badImage');

            return false;
        }

        return true;
    }
}
