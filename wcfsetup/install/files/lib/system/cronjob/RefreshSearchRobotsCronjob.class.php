<?php

namespace wcf\system\cronjob;

use GuzzleHttp\Psr7\Request;
use wcf\data\cronjob\Cronjob;
use wcf\system\cache\builder\SpiderCacheBuilder;
use wcf\system\io\HttpFactory;
use wcf\system\WCF;
use wcf\util\XML;

/**
 * Refreshes list of search robots.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class RefreshSearchRobotsCronjob extends AbstractCronjob
{
    /**
     * URL to the spider list.
     */
    private const SPIDER_LIST_URL = 'http://assets.woltlab.com/spiderlist/typhoon/list.xml';

    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        $client = HttpFactory::getDefaultClient();
        $request = new Request('GET', self::SPIDER_LIST_URL);
        $response = $client->send($request);

        $xml = new XML();
        $xml->loadXML('list.xml', (string)$response->getBody());

        $xpath = $xml->xpath();

        // fetch spiders
        $spiders = $xpath->query('/ns:data/ns:spider');

        if (!empty($spiders)) {
            $existingSpiders = SpiderCacheBuilder::getInstance()->getData();
            $statementParameters = [];

            /** @var \DOMElement $spider */
            foreach ($spiders as $spider) {
                $identifier = \mb_strtolower($spider->getAttribute('ident'));
                $name = $xpath->query('ns:name', $spider)->item(0);
                $info = $xpath->query('ns:url', $spider)->item(0);

                $statementParameters[$identifier] = [
                    'spiderIdentifier' => $identifier,
                    'spiderName' => $name->nodeValue,
                    'spiderURL' => $info ? $info->nodeValue : '',
                ];
            }

            if (!empty($statementParameters)) {
                $sql = "INSERT INTO             wcf" . WCF_N . "_spider
                                                (spiderIdentifier, spiderName, spiderURL)
                        VALUES                  (?, ?, ?)
                        ON DUPLICATE KEY UPDATE spiderName = VALUES(spiderName),
                                                spiderURL = VALUES(spiderURL)";
                $statement = WCF::getDB()->prepareStatement($sql);

                WCF::getDB()->beginTransaction();
                foreach ($statementParameters as $parameters) {
                    $statement->execute([
                        $parameters['spiderIdentifier'],
                        $parameters['spiderName'],
                        $parameters['spiderURL'],
                    ]);
                }
                WCF::getDB()->commitTransaction();
            }

            // delete obsolete entries
            $sql = "DELETE FROM wcf" . WCF_N . "_spider WHERE spiderIdentifier = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            foreach ($existingSpiders as $spider) {
                if (!isset($statementParameters[$spider->spiderIdentifier])) {
                    $statement->execute([$spider->spiderIdentifier]);
                }
            }

            // clear spider cache
            SpiderCacheBuilder::getInstance()->reset();
        }
    }
}
