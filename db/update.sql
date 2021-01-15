-- PRE PRE PRE doctrine-update =============================================================
alter table roundnumbers rename roundNumbers;
alter table planningconfigs rename planningConfigs;


-- POST POST POST doctrine-update ===========================================================
update sports set gameMode = 2, nrOfGamePlaces = 2;

-- scoreConfigs: fk to competitionSports needs to be not null again
-- fields: fk to competitionSports needs to be not null again
INSERT INTO competitionSports ( sportId, competitionId )( SELECT sportid, competitionid from sportconfigs );
INSERT INTO gameAmountConfigs ( amount, roundNumberId, competitionSportId )(
    SELECT pc.nrOfHeadtohead, rn.id, (select id from competitionSports where competitionId = rn.competitionId ) from roundNumbers rn join planningConfigs pc on rn.planningConfigId = pc.id
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
-- CHECK INSERT INTO qualifyAgainstConfigs
INSERT INTO qualifyAgainstConfigs ( winPoints, drawPoints, winPointsExt, drawPointsExt, losePointsExt, pointsCalculation, competitionSportId, roundId )(
    SELECT sc.winPoints, sc.drawPoints, sc.winPointsExt, sc.drawPointsExt, sc.losePointsExt, sc.pointsCalculation, (select id from competitionSports where competitionId = rn.competitionId ), r.id from rounds r join roundNumbers rn on r.numberId = rn.id join sportconfigs sc where sc.competitionid = rn.competitionid and rn.number = 1
);
INSERT INTO gamesAgainst (id, pouleid, resourcebatch, state, startDateTime, refereeId, placerefereeId, fieldId, competitionSportId )
    SELECT id, pouleid, resourcebatch, state, startDateTime, refereeId, placerefereeId, fieldId, (select id from competitionSports where competitionId = ( select rn.competitionId from poules p join rounds r on r.id = p.roundid join roundNumbers rn on rn.id = r.numberid where p.id = games.pouleid ) ) from games
;
INSERT INTO gamePlacesAgainst (homeAway, placeId, gameId)
(
    SELECT homeAway, placeId, gameId from gameplaces
);
INSERT INTO scoresAgainst (phase, number, homeScore, awayScore, gameId)
(
    SELECT phase, number, home, away, gameId from gamescores
);



-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
