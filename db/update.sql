-- PRE PRE PRE doctrine-update =============================================================
alter table roundnumbers rename roundNumbers;
alter table planningconfigs rename planningConfigs;
alter table qualifygroups rename qualifyGroups;

update competitors set registered = 0 where registered is null;

-- POST POST POST doctrine-update ===========================================================

update sports set defaultGameMode = 2, defaultNrOfSidePlaces = 1;
update sports set defaultGameMode = 2, defaultNrOfSidePlaces = 1;
update sports set customId = 15, defaultGameMode = 1, defaultNrOfSidePlaces = 0 where name = 'sjoelen';
update sports set name = 'klavrjassen' where name = 'klaverjassen';
insert into sports(name, team, customId, defaultGameMode, defaultNrOfSidePlaces ) values ('klaverjassen', false, 16, 2, 2 );
update qualifyGroups set target = 'W' where winnersOrLosers = 1;
update qualifyGroups set target = '' where winnersOrLosers = 2;
update qualifyGroups set target = 'L' where winnersOrLosers = 3;
update planningConfigs set editMode = 1;
update planningConfigs set gamePlaceStrategy = 1;
-- enable unique-constraints-qualifygroup again

-- scoreConfigs: fk to competitionSports needs to be not null again
-- fields: fk to competitionSports needs to be not null again
INSERT INTO competitionSports ( sportId, competitionId, gameMode, nrOfHomePlaces, nrOfAwayPlaces, nrOfGamePlaces, nrOfH2H, nrOfGamesPerPlace )( SELECT sportid, competitionid, 2, 1, 1, 0, 1, 0  from sportconfigs );
update fields f join sportconfigs sc on sc.id = f.sportConfigId set competitionSportId = ( select id from competitionSports where competitionId = sc.competitionId );
INSERT INTO gameAmountConfigs ( amount, nrOfGamesPerPlace, roundNumberId, competitionSportId )(
    SELECT pc.nrOfHeadtohead, 0, rn.id, (select id from competitionSports where competitionId = rn.competitionId ) from roundNumbers rn join planningConfigs pc on rn.planningConfigId = pc.id
);
-- parent is null
INSERT INTO scoreConfigs ( direction, maximum, enabled, parentId, competitionSportId, roundId )(
    SELECT ssc.direction, ssc.maximum, ssc.enabled, null, (select id from competitionSports where competitionId = rn.competitionId ), r.id from rounds r join roundNumbers rn on r.numberId = rn.id join sportscoreconfigs ssc on ssc.roundnumberid = rn.id and ssc.parentid is null
);
-- parent is not null
INSERT INTO scoreConfigs ( direction, maximum, enabled, parentId, competitionSportId, roundId )(
    SELECT ssc.direction, ssc.maximum, ssc.enabled, sc.id, sc.competitionSportId, sc.roundId
    from scoreConfigs sc join rounds r on r.id = sc.roundId join roundNumbers rn on r.numberId = rn.id join sportscoreconfigs ssc on ssc.roundnumberid = rn.id and ssc.parentid is not null
);
-- CHECK INSERT INTO againstQualifyConfigs
INSERT INTO againstQualifyConfigs ( winPoints, drawPoints, winPointsExt, drawPointsExt, losePointsExt, pointsCalculation, competitionSportId, roundId )(
    SELECT sc.winPoints, sc.drawPoints, sc.winPointsExt, sc.drawPointsExt, sc.losePointsExt, sc.pointsCalculation, (select id from competitionSports where competitionId = rn.competitionId ), r.id from rounds r join roundNumbers rn on r.numberId = rn.id join sportconfigs sc where sc.competitionid = rn.competitionid and rn.number = 1
);
INSERT INTO againstGames (id, pouleid, resourcebatch, state, startDateTime, refereeId, placerefereeId, fieldId, competitionSportId, gameRoundNumber )
    SELECT id, pouleid, resourcebatch, state, startDateTime, refereeId, placerefereeId, fieldId, (select id from competitionSports where competitionId = ( select rn.competitionId from poules p join rounds r on r.id = p.roundid join roundNumbers rn on rn.id = r.numberid where p.id = games.pouleid ) ), 0 from games
;
INSERT INTO againstGamePlaces (side, placeId, gameId)
(
    SELECT if(homeAway,1,2), placeId, gameId from gameplaces
);
INSERT INTO againstScores (phase, number, home, away, gameId)
(
    SELECT phase, number, home, away, gameId from gamescores
);



-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
