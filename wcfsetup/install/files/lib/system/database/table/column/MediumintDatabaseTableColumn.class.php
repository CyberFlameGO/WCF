<?php

namespace wcf\system\database\table\column;

/**
 * Represents a `mediumint` database table column.
 *
 * @author  Matthias Schmidt
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since   5.2
 */
final class MediumintDatabaseTableColumn extends AbstractIntDatabaseTableColumn
{
    /**
     * @inheritDoc
     */
    protected string $type = 'mediumint';

    /**
     * @inheritDoc
     */
    public function getMaximumLength(): int
    {
        return 9;
    }
}
