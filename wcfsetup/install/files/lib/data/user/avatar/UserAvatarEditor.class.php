<?php

namespace wcf\data\user\avatar;

use wcf\data\DatabaseObjectEditor;
use wcf\system\exception\NotImplementedException;
use wcf\system\image\ImageHandler;
use wcf\system\WCF;
use wcf\util\FileUtil;

/**
 * Provides functions to edit avatars.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method static UserAvatar  create(array $parameters = [])
 * @method      UserAvatar  getDecoratedObject()
 * @mixin       UserAvatar
 */
class UserAvatarEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = UserAvatar::class;

    /**
     * @inheritDoc
     */
    public function delete()
    {
        $sql = "DELETE FROM wcf" . WCF_N . "_user_avatar
                WHERE       avatarID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->avatarID]);

        $this->deleteFiles();
    }

    /**
     * @inheritDoc
     */
    public static function deleteAll(array $objectIDs = [])
    {
        $sql = "SELECT  *
                FROM    wcf" . WCF_N . "_user_avatar
                WHERE   avatarID IN (" . \str_repeat('?,', \count($objectIDs) - 1) . "?)";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($objectIDs);
        while ($avatar = $statement->fetchObject(self::$baseClass)) {
            $editor = new self($avatar);
            $editor->deleteFiles();
        }

        return parent::deleteAll($objectIDs);
    }

    /**
     * Deletes avatar files.
     */
    public function deleteFiles()
    {
        // delete original size
        @\unlink($this->getLocation(null, false));

        if ($this->hasWebP) {
            @\unlink($this->getLocation(null, true));
        }
    }

    /**
     * Creates a WebP variant of the avatar, unless it is a GIF image. If the
     * user uploads a WebP image, this method will create a JPEG variant as a
     * fallback for ancient clients.
     *
     * Will return `true` if a variant has been created.
     *
     * @since 5.4
     */
    public function createAvatarVariant(): bool
    {
        if ($this->hasWebP) {
            return false;
        }

        if ($this->avatarExtension === "gif") {
            // We do not touch GIFs at all.
            return false;
        }

        $filename = $this->getLocation();
        $filenameWebP = $this->getLocation(null, true);

        $imageAdapter = ImageHandler::getInstance()->getAdapter();
        $imageAdapter->loadFile($filename);
        $image = $imageAdapter->getImage();

        $data = ["hasWebP" => 1];

        // If the uploaded avatar is already a WebP image, then create a JPEG
        // as a fallback image and flip the image data to match the JPEG.
        if ($this->avatarExtension === "webp") {
            // This entire code path is completely broken, because writing into the
            // ->data array of the UserAvatarEditor does not actually do anything
            // for ->getLocation(), which is a method on the base UserAvatar.
            // This is also unreachable, because WebP files are rejected unconditionally
            // in AvatarUploadFileValidationStrategy.
            throw new NotImplementedException();
            $filenameJpeg = \preg_replace('~\.webp$~', '.jpeg', $filenameWebP);

            $imageAdapter->saveImageAs($image, $filenameJpeg, "jpeg", 80);

            // The new file has a different SHA1 hash, which means that apart from
            // updating the `fileHash` we also need to move it to a different physical
            // location.
            $newFileHash = \sha1_file($filenameJpeg);

            $tmpAvatar = clone $this;
            $tmpAvatar->data["avatarExtension"] = "jpeg";
            $tmpAvatar->data["fileHash"] = $newFileHash;
            $newLocation = $tmpAvatar->getLocation(null, false);

            $dir = \dirname($newLocation);
            if (!\file_exists($dir)) {
                FileUtil::makePath($dir);
            }

            \rename($filenameJpeg, $newLocation);

            $data = [
                "avatarExtension" => "jpeg",
                "fileHash" => $newFileHash,
            ];
        } else {
            $imageAdapter->saveImageAs($image, $this->getLocation(null, true), "webp", 80);
        }

        $this->update($data);

        return true;
    }
}
