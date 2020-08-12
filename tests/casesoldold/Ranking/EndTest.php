<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 11:48
 */

namespace Sports\Tests\Ranking;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use Sports\Ranking\End as EndRanking;

class EndTest extends \PHPUnit\Framework\TestCase
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

        $endRanking = new EndRanking();

        $items = $endRanking->getItems($structure->getRootRound());
        $this->assertSame($items[0]->getPoulePlace()->getCompetitor()->getName(), 'jil');
        $this->assertSame($items[1]->getPoulePlace()->getCompetitor()->getName(), 'max');

        $this->assertSame($items[2]->getPoulePlace()->getCompetitor()->getName(), 'zed');
        $this->assertSame($items[3]->getPoulePlace()->getCompetitor()->getName(), 'jip');

        $this->assertSame($items[4]->getPoulePlace()->getCompetitor()->getName(), 'jan');

        $this->assertSame($items[5]->getPoulePlace()->getCompetitor()->getName(), 'jos');
        $this->assertSame($items[6]->getPoulePlace()->getCompetitor()->getName(), 'wim');

        $this->assertSame($items[7]->getPoulePlace()->getCompetitor()->getName(), 'cor');
        $this->assertSame($items[8]->getPoulePlace()->getCompetitor()->getName(), 'pim');
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

        $endRanking = new EndRanking();

        $items = $endRanking->getItems($structure->getRootRound());
        $this->assertSame($items[0]->getPoulePlace()->getCompetitor()->getName(), 'tiem');
        $this->assertSame($items[1]->getPoulePlace()->getCompetitor()->getName(), 'kira');
        $this->assertSame($items[2]->getPoulePlace()->getCompetitor()->getName(), 'luuk');
        $this->assertSame($items[3]->getPoulePlace()->getCompetitor()->getName(), 'bart');
        $this->assertSame($items[4]->getPoulePlace()->getCompetitor()->getName(), 'mira');
        $this->assertSame($items[5]->getPoulePlace()->getCompetitor()->getName(), 'huub');
        $this->assertSame($items[6]->getPoulePlace()->getCompetitor()->getName(), 'nova');
        $this->assertSame($items[7]->getPoulePlace()->getCompetitor()->getName(), 'mats');
        $this->assertSame($items[8]->getPoulePlace()->getCompetitor()->getName(), 'bram');
        $this->assertSame($items[9]->getPoulePlace()->getCompetitor()->getName(), 'stan');
        $this->assertSame($items[10]->getPoulePlace()->getCompetitor()->getName(), 'maan');
        $this->assertSame($items[11]->getPoulePlace()->getCompetitor()->getName(), 'mila');
        $this->assertSame($items[12]->getPoulePlace()->getCompetitor()->getName(), 'noud');
        $this->assertSame($items[13]->getPoulePlace()->getCompetitor()->getName(), 'mart');
        $this->assertSame($items[14]->getPoulePlace()->getCompetitor()->getName(), 'fred');
        $this->assertSame($items[15]->getPoulePlace()->getCompetitor()->getName(), 'toon');
    }

    public function testStructure4Teamup()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'Sports\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure4rankteamup.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'Sports\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        $endRanking = new EndRanking();

        $items = $endRanking->getItems($structure->getRootRound());
        $this->assertSame($items[0]->getPoulePlace()->getCompetitor()->getName(), 'rank1');
        $this->assertSame($items[1]->getPoulePlace()->getCompetitor()->getName(), 'rank2');
        $this->assertSame($items[2]->getPoulePlace()->getCompetitor()->getName(), 'rank3');
        $this->assertSame($items[3]->getPoulePlace()->getCompetitor()->getName(), 'rank4');
    }
}
