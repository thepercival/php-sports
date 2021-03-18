<?php

namespace Sports\Output\ConsoleTable;

use DateTime;
use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\Game\Against as AgainstGameBase;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Event\Goal as GoalEvent;
use Sports\Game\Event\Card as CardEvent;
use Sports\Game\Event\Substitution as SubstitutionEvent;
use Sports\NameService;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Sport;
use Sports\Score\Config\Service as ScoreConfigService;
use SportsHelpers\Against\Side as AgainstSide;

class AgainstGame
{
    protected CompetitorMap $competitorMap;
    protected NameService $nameService;
    protected AgainstGameBase $game;

    /**
     * @param Competition $competition
     * @param AgainstGameBase $game
     * @param array<TeamCompetitor> $teamCompetitors
     *
     * @return void
     */
    public function display(Competition $competition, AgainstGameBase $game, array $teamCompetitors): void
    {
        $table = new ConsoleTable();
        // $table->setHeaders(array('league', 'season', 'batchNr', 'id', 'datetime', 'state', 'home', 'score', 'away' ) );

        $this->competitorMap = new CompetitorMap($teamCompetitors);
        $this->nameService = new NameService($this->competitorMap);
        $this->game = $game;

        $table->addRow($this->getGameRow($competition));
        $table->addRow($this->getScoreRow());
        $table->addRow(["", "", ""]);

        $this->displayLineups($table);
        $table->addRow(["", "", ""]);

        $this->displayEvents($table);

        $table->display();
    }

    protected function displayLineups(ConsoleTable $table): void
    {
        $homeParticipations = $this->getLineup(AgainstSide::HOME);
        $awayParticipations = $this->getLineup(AgainstSide::AWAY);
        while (count($homeParticipations) > 0 || count($awayParticipations) > 0) {
            $homeParticipationName = "";
            $homeParticipation = array_pop($homeParticipations);
            if ($homeParticipation !== null) {
                $homeParticipationName = $homeParticipation->getPlayer()->getLineLetter() . " ";
                $homeParticipationName .= $homeParticipation->getPlayer()->getPerson()->getName();
            }
            $awayParticipationName = "";
            $awayParticipation = array_pop($awayParticipations);
            if ($awayParticipation !== null) {
                $awayParticipationName = $awayParticipation->getPlayer()->getLineLetter() . " ";
                $awayParticipationName .= $awayParticipation->getPlayer()->getPerson()->getName();
                // $awayParticipationName .= " : " . $awayParticipation->getBeginMinute() . " => " . $awayParticipation->getEndMinute();
            }
            $table->addRow([  $homeParticipationName, "", $awayParticipationName ]);
        }
    }

    protected function displayEvents(ConsoleTable $table): void
    {
        foreach ($this->game->getEvents() as $event) {
            foreach ($this->getEventRows($event) as $eventRow) {
                $table->addRow($eventRow);
            }
        }
    }

    /**
     * @param GoalEvent|CardEvent|SubstitutionEvent $event
     * @return array
     */
    protected function getEventRows($event): array
    {
        $rows = [];
        foreach ([AgainstSide::HOME,AgainstSide::AWAY] as $side) {
            foreach ($this->game->getCompetitors($this->competitorMap, $side) as $competitor) {
                if (!($competitor instanceof TeamCompetitor) || $competitor->getTeam() !== $event->getTeam()) {
                    continue;
                }
                $rows = array_merge($rows, $this->getEventRowsHelper($event, $side));
            }
        }
        return $rows;
    }

    /**
     * @param GoalEvent|CardEvent|SubstitutionEvent $event
     * @param int $side
     * @return array
     */
    protected function getEventRowsHelper($event, int $side): array
    {
        if ($event instanceof GoalEvent) {
            return $this->getGoalEventRows($event, $side);
        } elseif ($event instanceof CardEvent) {
            return $this->getCardEventRows($event, $side);
        } // else if( $event instanceof SubstitutionEvent ) {
        return $this->getSubstituteEventRows($event, $side);
        // }
        // return [];
    }

    protected function getGoalEventRows(GoalEvent $event, int $side): array
    {
        $valueHome = "";
        $valueAway = "";
        $rows = [];
        if ($side === AgainstSide::HOME) {
            $valueHome .= "GL  ";
            $valueHome .= $event->getMinute() . "' ";
            $valueHome .= $event->getGameParticipation()->getPlayer()->getPerson()->getName();
        } else {
            $valueAway .= $event->getGameParticipation()->getPlayer()->getPerson()->getName();
            $valueAway .= " " . $event->getMinute() . "'";
            $valueAway .= "  GL";
        }
        $rows[] = [  $valueHome, "", $valueAway ];
        return $rows;
    }

    protected function getCardEventRows(CardEvent $event, int $side): array
    {
        $valueHome = "";
        $valueAway = "";
        $rows = [];
        if ($side === AgainstSide::HOME) {
            if ($event->getType() === Sport::WARNING) {
                $valueHome .= "YC  ";
            } else {
                $valueHome .= "RC  ";
            }
            $valueHome .= $event->getMinute() . "' ";
            $valueHome .= $event->getGameParticipation()->getPlayer()->getPerson()->getName();
        } else {
            $valueAway .= $event->getGameParticipation()->getPlayer()->getPerson()->getName();
            $valueAway .= " " . $event->getMinute() . "'";
            if ($event->getType() === Sport::WARNING) {
                $valueAway .= "  YC";
            } else {
                $valueAway .= "  RC  ";
            }
        }
        $rows[] = [  $valueHome, "", $valueAway ];
        return $rows;
    }

    protected function getSubstituteEventRows(SubstitutionEvent $event, int $side): array
    {
        $valueHomeOut = "";
        $valueAwayOut = "";
        $valueHomeIn = "";
        $valueAwayIn = "";
        $rows = [];
        if ($side === AgainstSide::HOME) {
            $valueHomeOut .= "OUT ";
            $valueHomeOut .= $event->getMinute() . "' ";
            $valueHomeOut .= $event->getOut()->getPlayer()->getPerson()->getName();
            $valueHomeIn .= "IN  ";
            $valueHomeIn .= $event->getMinute() . "' ";
            $valueHomeIn .= $event->getIn()->getPlayer()->getPerson()->getName();
        } else {
            $valueAwayOut .= $event->getOut()->getPlayer()->getPerson()->getName();
            $valueAwayOut .= " " . $event->getMinute() . "'";
            $valueAwayOut .= " OUT";
            $valueAwayIn .= $event->getIn()->getPlayer()->getPerson()->getName();
            $valueAwayIn .= " " . $event->getMinute() . "'";
            $valueAwayIn .= "  IN";
        }
        $rows[] = [  $valueHomeIn, "", $valueAwayIn ];
        $rows[] = [  $valueHomeOut, "", $valueAwayOut ];
        return $rows;
    }

    /**
     * @param int $side
     * @return array<GameParticipation>
     */
    protected function getLineup(int $side): array
    {
        $participations = [];
        $homeCompetitors = $this->game->getCompetitors($this->competitorMap, $side);
        foreach ($homeCompetitors as $homeTeamCompetitor) {
            if (!($homeTeamCompetitor instanceof TeamCompetitor)) {
                continue;
            }
            $participations = array_merge(
                $participations,
                $this->game->getLineup($homeTeamCompetitor)
            );
        }
        return $participations;
    }


    //| Fortuna Sittard                               | 1 - 3   | Heerenveen                                    |
    //|                                               |         |                                               |
    //| alexei-koselev/98078      K Alexei Koşelev    |         | erwin-mulder/19019        K Erwin Mulder      |
    //| roel-janssen/110360       V Roel Janssen      |         | jan-paul-van-hecke/962012 V Jan Paul van Heck |
    //| lazaros-rota/941338       V Lazaros Rota      |         | pawel-bochniewicz/286097  V Paweł Bochniewic |
    //| george-cox/920556         V George Cox        |         | lucas-woudenberg/282705   V Lucas Woudenberg  |
    //| branislav-ninaj/193328    V Branislav Niňaj   |         | sherel-floranus/803021    V Sherel Floranus   |
    //| jorrit-smeets/770183      M Jorrit Smeets     |         | joey-veerman/850816       M Joey Veerman      |
    //| ben-rienstra/123879       M Ben Rienstra      |         | mitchell-van-bergen/82766 M Mitchell Van Berg |
    //| mats-seuntjens/163541     M Mats Seuntjens    |         | arjen-van-der-heide/91700 M Arjen Van Der Hei |
    //| sebastian-polter/39733    A Sebastian Polter  |         | kongolo-rodney/792319     M Rodney Kongolo    |
    //| flemming-zian/875137      A Zian Flemming     |         | henk-veerman/313264       A Henk Veerman      |
    //| emil-hansson/794362       A Emil Hansson      |         | meier-oliver-batista/9076 A Oliver Batista Me |
    //|  ----------------------------                 |         |  ----------------------------                 |
    //|                                               | 0 - 1   | 15"  GOL Oliver Batista Meier                 |
    // |                                               |         |      ASS Henk Veerman                         |
    // |                                               | 0 - 2   | 29"  PEN Joey Veerman                         |
    //|                                               | 0 - 3   | 34"  GOL Henk Veerman                         |
    // |                                               |         |      ASS Arjen Van Der Heide                  |
    // | 46"  OUT Ben Rienstra                         |         |                                               |
    //|      IN  Tesfaldet Tekie                      |         |                                               |
    //| 46"  OUT Emil Hansson                         |         |                                               |
    // |      IN  Lisandro Semedo                      |         |                                               |
    // | 62"  YC  Roel Janssen                         |         |                                               |
    //| 69"  YC  Branislav Niňaj                      |         |                                               |
    // |                                               |         | 69"  OUT Oliver Batista Meier                 |
    //|                                               |         |      IN  Rami Hajal                           |
    //| 72"  YC  Lazaros Rota                         |         |                                               |
    // | 73"  GOL Sebastian Polter                     | 1 - 3   |                                               |
    //|      ASS Lisandro Semedo                      |         |                                               |
    //| 87"  YC  Zian Flemming                        |         |                                               |
    // |                                               |         | 90"  OUT Arjen Van Der Heide                  |
    //|                                               |         |      IN  Couhaib Driouech                     |

    /**
     * @param Competition $competition
     * @return array|string[]
     */
    protected function getGameRow(Competition $competition): array
    {
        return [
            $competition->getLeague()->getName(),
            $competition->getSeason()->getName(),
            $this->game->getBatchNr() . ' : ' . $this->game->getStartDateTime()->format(DateTime::ATOM)
        ];
    }

    /**
     * @return array<string>
     */
    protected function getScoreRow(): array
    {
        $scoreConfigService = new ScoreConfigService();
        $finalScore = $scoreConfigService->getFinalAgainstScore($this->game);

        $score = " - ";
        if ($finalScore !== null) {
            $score = $finalScore->getHome() . $score . $finalScore->getAway();
        }
        $homePlaces = $this->game->getSidePlaces(AgainstSide::HOME);
        $awayPlaces = $this->game->getSidePlaces(AgainstSide::AWAY);
        return [
            $this->nameService->getPlacesFromName($homePlaces, true, true),
            $score,
            $this->nameService->getPlacesFromName($awayPlaces, true, true)
        ];
    }

//    function getGame( Voetbal_Competition $oCompetition, Voetbal_Season $oSeason, int $nGameId) {
//        $externSystem = getExternalLib();
//
//        $oExternGame = $externSystem->getGame(
//            Import_Factory::getIdFromExternId( $oCompetition->getExternId() ),
//            Import_Factory::getIdFromExternId( $oSeason->getExternId() ),
//            $nGameId
//        );
//
//
//        $arrParticipations = getStartGameParticipations(  $oExternGame );
//        /** @var Voetbal_Extern_Game_Participation $oHomeParticipant */
//        foreach( $arrParticipations as $oParticipations ) {
//            $draw = function( Voetbal_Extern_Game_Participation $p ): string {
//                return drawVal( $p->getPlayerPeriod()->getPerson()->getId(), 25 ) .
//                    " " . Voetbal_Team_Line::getAbb( $p->getPlayerPeriod()->getLine() ) .
//                    " " . $p->getPlayerPeriod()->getPerson()->getName();
//            };
//            drawGaneDetailLine(
//                $oParticipations->home ? $draw( $oParticipations->home ) : "?",
//                "",
//                $oParticipations->away ? $draw( $oParticipations->away ) : "?"
//            );
//        }
//
//        drawGaneDetailLine( " ---------------------------- ", ""," ---------------------------- " );
//
//        foreach( $oExternGame->getEvents() as $oEvent ) {
//            drawEvent( $oEvent );
//        }
//    }
//
//    function getStartGameParticipations( Voetbal_Extern_GameExt $oGame ): array {
//        $toArr = function( Patterns_Collection $p ): array {
//            $arr = [];
//            foreach( $p as $pIt ) {
//                if( $pIt->getIn() > 0 ) {
//                    continue;
//                }
//                $arr[] = $pIt;
//            }
//            uasort( $arr, function( $p1, $p2 ) {
//                return $p1->getPlayerPeriod()->getLine() > $p2->getPlayerPeriod()->getLine()  ? -1 : 1;
//            });
//            return $arr;
//        };
//        $home = $toArr($oGame->getParticipations( Voetbal_Game::HOME ));
//        $away = $toArr($oGame->getParticipations( Voetbal_Game::AWAY ));
//        $arrP = [];
//        for( $i = 0 ; $i < 11 ; $i++ ) {
//            $std = new stdClass();
//            $std->home = array_pop($home);
//            $std->away = array_pop($away);
//            $arrP[] = $std;
//        }
//        return $arrP;
//    }
//
//    function drawEvent( Voetbal_Extern_Game_Event $oEvent ) {
//        $oEventTeam = $oEvent->getGameParticipation()->getPlayerPeriod()->getTeam();
//        $oGame = $oEvent->getGameParticipation()->getGame();
//        $nHomeAway = $oEventTeam->getId() === $oGame->getHomeTeam()->getId() ? Voetbal_Game::HOME : Voetbal_Game::AWAY;
//        $sLeftRight = drawEventContent( $oEvent );
//        $sMiddle = drawEventMiddle( $oEvent );
//        drawGaneDetailLine(
//            $nHomeAway === Voetbal_Game::HOME ? $sLeftRight : "",
//            $sMiddle,
//            $nHomeAway === Voetbal_Game::AWAY ? $sLeftRight : ""
//        );
//        if( $oEvent instanceof Voetbal_Extern_Game_Event_Goal && $oEvent->getAssist() !== null ) {
//            $sAssistContent = drawEventContentAssist( $oEvent );
//            drawGaneDetailLine(
//                $nHomeAway === Voetbal_Game::HOME ? $sAssistContent : "",
//                "",
//                $nHomeAway === Voetbal_Game::AWAY ? $sAssistContent : ""
//            );
//        } else if( $oEvent instanceof Voetbal_Extern_Game_Event_Substitution ) {
//            $sSubContent = drawEventContentSubIn( $oEvent );
//            drawGaneDetailLine(
//                $nHomeAway === Voetbal_Game::HOME ? $sSubContent : "",
//                "",
//                $nHomeAway === Voetbal_Game::AWAY ? $sSubContent : ""
//            );
//        }
//    }
//
//    function drawEventContent( Voetbal_Extern_Game_Event $oEvent ): string {
//        $sContent = drawVal($oEvent->getMinute() . "\"", 4 );
//        $sContent .= " " . drawVal( getEventContentType($oEvent), 3);
//        $sContent .= " " . $oEvent->getGameParticipation()->getPlayerPeriod()->getPerson()->getName();
//        return $sContent;
//    }
//
//    function drawEventContentSubIn( Voetbal_Extern_Game_Event_Substitution $oEvent ): string {
//        $sContent = drawVal("", 4 );
//        $sContent .= " " . drawVal( "IN", 3);
//        $sContent .= " " . $oEvent->getIn()->getPlayerPeriod()->getPerson()->getName();
//        return $sContent;
//    }
//
//    function drawEventContentAssist( Voetbal_Extern_Game_Event_Goal $oEvent ): string {
//        $sContent = drawVal("", 4 );
//        $sContent .= " " . drawVal( "ASS", 3);
//        $sContent .= " " . $oEvent->getAssist()->getPlayerPeriod()->getPerson()->getName();
//        return $sContent;
//    }
//
//    function getEventContentType( Voetbal_Extern_Game_Event $oEvent ): string {
//        if( $oEvent instanceof Voetbal_Extern_Game_Event_Card ) {
//            if( $oEvent->getCard() === Voetbal_Game::DETAIL_YELLOWCARDONE ) {
//                return "YC";
//            } else if( $oEvent->getCard() === Voetbal_Game::DETAIL_YELLOWCARDTWO ) {
//                return "YC2";
//            } else if( $oEvent->getCard() === Voetbal_Game::DETAIL_REDCARD ) {
//                return "RC";
//            }
//        } else if( $oEvent instanceof Voetbal_Extern_Game_Event_Substitution ) {
//            return "OUT";
//        } else if( $oEvent instanceof Voetbal_Extern_Game_Event_Goal ) {
//            if( $oEvent->getOwn() ) {
//                return "OWN";
//            } else if( $oEvent->getPenalty() ) {
//                return "PEN";
//            } else {
//                return "GOL";
//            }
//        }
//        return "";
//    }
//
//    function drawEventMiddle( Voetbal_Extern_Game_Event $oEvent ): string {
//        if( $oEvent instanceof Voetbal_Extern_Game_Event_Goal ) {
//            return $oEvent->getHome() . ' - ' . $oEvent->getAway();
//        }
//        return '';
//    }
}
