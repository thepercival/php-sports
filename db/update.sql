-- PRE PRE PRE doctrine-update =============================================================
alter table roundnumbers rename roundNumbers;
alter table planningconfigs rename planningConfigs;
alter table qualifygroups rename qualifyGroups;

-- alter table rounds CHANGE parentQualifyId parentQualifyGroupId int NULL;

-- update data in rounds and qualifygroups

-- POST POST POST doctrine-update ===========================================================


-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
