<?php

/**
 * Checks the increased system requirements.
 *
 * @author  Tim Duesterhus
 * @copyright   2001-2022 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\WCF;

$phpVersion = \PHP_VERSION;
$neededPhpVersion = '8.1.2';
if (!\version_compare($phpVersion, $neededPhpVersion, '>=')) {
    if (WCF::getLanguage()->getFixedLanguageCode() === 'de') {
        $message = "Ihre PHP-Version '{$phpVersion}' ist unzureichend f&uuml;r die Installation dieser Software. PHP-Version {$neededPhpVersion} oder h&ouml;her wird ben&ouml;tigt.";
    } else {
        $message = "Your PHP version '{$phpVersion}' is insufficient for installation of this software. PHP version {$neededPhpVersion} or greater is required.";
    }

    throw new \RuntimeException($message);
}

if (\PHP_INT_SIZE != 8) {
    if (WCF::getLanguage()->getFixedLanguageCode() === 'de') {
        $message = "Die eingesetzte PHP-Version muss 64-Bit-Ganzzahlen unterst&uuml;tzen.";
    } else {
        $message = "The PHP version must support 64-bit integers.";
    }

    throw new \RuntimeException($message);
}

if (!\extension_loaded('intl')) {
    if (WCF::getLanguage()->getFixedLanguageCode() === 'de') {
        $message = "Die 'intl' PHP-Erweiterung muss aktiv sein.";
    } else {
        $message = "The 'intl' PHP extension needs to be enabled.";
    }

    throw new \RuntimeException($message);
}

$sqlVersion = WCF::getDB()->getVersion();
$compareSQLVersion = \preg_replace('/^(\d+\.\d+\.\d+).*$/', '\\1', $sqlVersion);
if (\stripos($sqlVersion, 'MariaDB') !== false) {
    $neededSqlVersion = '10.5.12';
    $sqlFork = 'MariaDB';
} else {
    $sqlFork = 'MySQL';
    $neededSqlVersion = '8.0.29';
}

if (!\version_compare($compareSQLVersion, $neededSqlVersion, '>=')) {
    if (WCF::getLanguage()->getFixedLanguageCode() === 'de') {
        $message = "Ihre {$sqlFork}-Version '{$sqlVersion}' ist unzureichend f&uuml;r die Installation dieser Software. {$sqlFork}-Version {$neededSqlVersion} oder h&ouml;her wird ben&ouml;tigt.";
    } else {
        $message = "Your {$sqlFork} version '{$sqlVersion}' is insufficient for installation of this software. {$sqlFork} version {$neededSqlVersion} or greater is required.";
    }

    throw new \RuntimeException($message);
}

$sql = "SELECT 1";
$statement = WCF::getDB()->prepareStatement($sql);
$statement->execute();
if ($statement->fetchSingleColumn() !== 1) {
    if (WCF::getLanguage()->getFixedLanguageCode() === 'de') {
        $message = "F&uuml;r die Kommunikation mit dem MySQL-Server muss PHPs MySQL Native Driver verwendet werden.";
    } else {
        $message = "PHP's MySQL Native Driver must be used for communication with the MySQL server.";
    }

    throw new \RuntimeException($message);
}
