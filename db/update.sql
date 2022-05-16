-- PRE PRE PRE doctrine-update =============================================================

alter table planningConfigs
    drop gamePlaceStrategy;

update competitionSports
set defaultPointsCalculation = (
    select aqc.pointsCalculation
    from againstQualifyConfigs aqc
             join rounds r on r.id = aqc.roundId
             join roundNumbers rn on rn.id = r.numberid
    where aqc.competitionSportId = competitionSports.Id
      and rn.number = 1
);

insert into categories(number, name, competitionId) (select 1, 'standaard', competitionId
                                                     from roundNumbers
                                                     where previousId is null)

update rounds join roundNumbers rn on rn.id = rounds.numberId
set rounds.categoryId = (select id from categories where competitionId = rn.competitionId);

-- alter table rounds CHANGE parentQualifyId parentQualifyGroupId int NULL;

-- update data in rounds and qualifygroups

-- POST POST POST doctrine-update ===========================================================


-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
