<?php

namespace Sports\Tests\Structure;

use Sports\Structure\PostCreateService;

include_once __DIR__ . '/Check332a.php';
include_once __DIR__ . '/../../helpers/Serializer.php';
//include_once __DIR__ . '/../../helpers/PostSerialize.php';

//use Sports\Structure;
//use Sports\Qualify\Group as QualifyGroup;

class SerializerTest extends \PHPUnit\Framework\TestCase
{
    use Check332a;

    public function testSerializing332a()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        if ($json_raw === false) {
            throw new \Exception("competition-json not read well from file", E_ERROR);
        }
        $json = json_decode($json_raw, true);
        if ($json === false) {
            throw new \Exception("competition-json not read well from file", E_ERROR);
        }
        $jsonEncoded = json_encode($json);
        if ($jsonEncoded === false) {
            throw new \Exception("competition-json not read well from file", E_ERROR);
        }
        $competition = $serializer->deserialize($jsonEncoded, 'Sports\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure/332a.json");
        if ($json_raw === false) {
            throw new \Exception("structure-json not read well from file", E_ERROR);
        }
        $json = json_decode($json_raw, true);
        if ($json === false) {
            throw new \Exception("structure-json not read well from file", E_ERROR);
        }
        $jsonEncoded = json_encode($json);
        if ($jsonEncoded === false) {
            throw new \Exception("structure-json not read well from file", E_ERROR);
        }
        $structure = $serializer->deserialize($jsonEncoded, 'Sports\Structure', 'json');
        $postCreateService = new PostCreateService($structure);
        $postCreateService->create();

        $this->check332astructure($structure);
    }
}
