-- PRE PRE PRE doctrine-update =============================================================
alter table sportscoreconfigs rename sportScoreConfigs;
alter table roundnumbers rename roundNumbers;
alter table planningconfigs rename planningConfigs;


-- POST POST POST doctrine-update ===========================================================
update games set gameRoundNumber = 0;
update sports set gameMode = 2, nrOfGamePlaces = 2;

-- sportScoreConfigs: fk to competitionSports needs to be not null again
-- fields: fk to competitionSports needs to be not null again
INSERT INTO competitionSports ( sportId, competitionId )( SELECT sportid, competitionid from sportconfigs );
INSERT INTO gameAmountConfigs ( amount, roundNumberId, competitionSportId )(
    SELECT pc.nrOfHeadtohead, rn.id, (select id from competitionSports where competitionId = rn.competitionId ) from roundNumbers rn join planningConfigs pc on rn.planningConfigId = pc.id
);
INSERT INTO qualifyAgainstConfigs ( winPoints, drawPoints, winPointsExt, drawPointsExt, losePointsExt, pointsCalculation, competitionSportId, roundNumberId )(
    SELECT sc.winPoints, sc.drawPoints, sc.winPointsExt, sc.drawPointsExt, sc.losePointsExt, sc.pointsCalculation, (select id from competitionSports where competitionId = rn.competitionId ), rn.id from roundNumbers rn join sportconfigs sc where sc.competitionid = rn.competitionid and rn.number = 1
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
