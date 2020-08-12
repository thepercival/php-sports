<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 3-1-19
 * Time: 14:49
 */

namespace Sports\Tests\Structure2;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use Sports\Structure\NameService;
use Sports\Round;

class NameServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testStructure9()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Sports\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure9.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Sports\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        foreach ($structure->getRound([])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'wim');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'max');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jan');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jip');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jil');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jos');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'zed');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'cor');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'pim');
            }
        }

        foreach ($structure->getRound([Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'max');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'zed');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jip');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), '?2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jil');
            }
        }

        foreach ($structure->getRound([Round::LOSERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), '?2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'cor');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jos');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'wim');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'pim');
            }
        }

        foreach ($structure->getRound([Round::WINNERS,Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'max');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'E1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jil');
            }
        }

        foreach ($structure->getRound([Round::WINNERS,Round::LOSERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'zed');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'E2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jip');
            }
        }

        foreach ($structure->getRound([Round::LOSERS,Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'J1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'F1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'jos');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'J2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'G1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'wim');
            }
        }

        foreach ($structure->getRound([Round::LOSERS,Round::LOSERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'K1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'F2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'cor');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'K2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'G2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'pim');
            }
        }
    }

    public function testStructure16()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Sports\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure16rank.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Sports\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        foreach ($structure->getRound([])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'maan');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'tiem');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'noud');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'nova');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'fred');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'bart');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'stan');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'huub');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'luuk');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mart');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mats');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mila');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mira');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'kira');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'toon');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'bram');
            }
        }

        foreach ($structure->getRound([Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'tiem');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'bart');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'luuk');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'kira');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'nova');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'huub');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mats');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mira');
            }
        }

        foreach ($structure->getRound([Round::LOSERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'maan');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'stan');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'J1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mila');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'J2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D3');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'bram');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'K1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'noud');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'K2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'fred');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'L1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mart');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'L2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D4');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'toon');
            }
        }

        foreach ($structure->getRound([Round::WINNERS, Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'M1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'E1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'tiem');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'M2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'F1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'kira');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'N1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'E2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'bart');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'N2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'F2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'luuk');
            }
        }

        foreach ($structure->getRound([Round::WINNERS, Round::LOSERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'O1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'G1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'huub');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'O2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'H1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mira');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'P1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'G2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'nova');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'P2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'H2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mats');
            }
        }

        foreach ($structure->getRound([Round::LOSERS, Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'Q1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'I1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'stan');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'Q2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'J1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'bram');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'R1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'I2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'maan');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'R2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'J2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mila');
            }
        }

        foreach ($structure->getRound([Round::LOSERS, Round::LOSERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */
            if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'S1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'K1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'noud');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'S2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'L1');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'mart');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'T1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'K2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'fred');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'T2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'L2');
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, true), 'toon');
            }
        }
    }

    public function testStructure163Poules()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Sports\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure163poules.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Sports\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        foreach ($structure->getRound([])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A1');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A2');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A3');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A4');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 5) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A5');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 6) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A6');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B1');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B2');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B3');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B4');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 5) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B5');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C1');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C2');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C3');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 4) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C4');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 5) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C5');
            }
        }

        foreach ($structure->getRound([Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A1');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B1');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C1');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A2');
            } elseif ($poulePlace->getPoule()->getNumber() === 5 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B2');
            } elseif ($poulePlace->getPoule()->getNumber() === 6 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C2');
            } elseif ($poulePlace->getPoule()->getNumber() === 7 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'J1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A3');
            } elseif ($poulePlace->getPoule()->getNumber() === 8 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'K1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B3');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C3');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A4');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B4');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C4');
            } elseif ($poulePlace->getPoule()->getNumber() === 5 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A5');
            } elseif ($poulePlace->getPoule()->getNumber() === 6 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B5');
            } elseif ($poulePlace->getPoule()->getNumber() === 7 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'J2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C5');
            } elseif ($poulePlace->getPoule()->getNumber() === 8 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'K2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A6');
            }
        }
    }

    public function testStructure15()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Sports\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure15.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Sports\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        foreach ($structure->getRound([])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A1');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A2');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'A3');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B1');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B2');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'B3');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C1');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C2');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'C3');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D1');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D2');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'D3');
            } elseif ($poulePlace->getPoule()->getNumber() === 5 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E1');
            } elseif ($poulePlace->getPoule()->getNumber() === 5 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E2');
            } elseif ($poulePlace->getPoule()->getNumber() === 5 && $poulePlace->getNumber() === 3) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'E3');
            }
        }

        foreach ($structure->getRound([Round::WINNERS])->getPoulePlaces() as $poulePlace) {
            $nameService = new NameService();
            /*  */if ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'A1');
            } elseif ($poulePlace->getPoule()->getNumber() === 1 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'F2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'E1');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'B1');
            } elseif ($poulePlace->getPoule()->getNumber() === 2 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'G2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), '?2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, true), 'c2');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'C1');
            } elseif ($poulePlace->getPoule()->getNumber() === 3 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'H2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), '?2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, true), 'd2');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 1) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I1');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), 'D1');
            } elseif ($poulePlace->getPoule()->getNumber() === 4 && $poulePlace->getNumber() === 2) {
                $this->assertSame($nameService->getPoulePlaceName($poulePlace, false), 'I2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, false), '?2');
                $this->assertSame($nameService->getPoulePlaceFromName($poulePlace, true), 'b2');
            }
        }
    }
}
