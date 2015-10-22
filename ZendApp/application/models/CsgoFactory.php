<?php

class CsgoFactory extends ModelFactory {

    public static function getMapStats($teamSize = null) {
        $sql = "select
                    m.map as map_name,
                    r.side as side,
                    sum(r.won) as round_wins,
                    count(r.won) - sum(r.won) as round_losses,
                    count(r.won) as round_played,
                    p.pistol_round_wins,
                    p.pistol_round_losses,
                    p.pistol_round_played,
                    m2.matches_won,
                    m2.matches_tied,
                    m2.matches_lost,
                    m2.win_percent,
                    m2.not_lose_percent,
                    (m2.matches_won + m2.matches_tied + m2.matches_lost) as matches_played
                from tb_csgo_round r
                join tb_csgo_match m
                on m.pk_csgo_match_id = r.fk_pk_csgo_match_id
                join (
                    select
                        map,
                        sum(ir.won) as pistol_round_wins,
                        count(ir.won) - sum(ir.won) as pistol_round_losses,
                        count(ir.won) as pistol_round_played
                    from tb_csgo_round ir
                    join tb_csgo_match im
                    on im.pk_csgo_match_id = ir.fk_pk_csgo_match_id
                    where round_number in (1,16)
                    and ir.side = 'T' " . ($teamSize == null ? '' : "and im.team_size = :size")."
                    group by im.map
                ) p
                on m.map = p.map
                join (
                    select
                        map,
                        sum(won) as matches_won,
                        count(won) - sum(won) - sum(tied) as matches_lost,
                        sum(tied) matches_tied,
                        round((sum(won)+sum(tied))/count(won)*100) as not_lose_percent,
                        round((sum(won))/count(won)*100) as win_percent
                    from tb_csgo_match im2
                    " . ($teamSize == null ? '' : "where im2.team_size = :size"). "
                    group by map
                ) m2
                on m.map = m2.map
                where r.side = 'T' " . ($teamSize == null ? '' : "and m.team_size = :size") . "
                group by m.map

                UNION (
                    select
                        m.map as map_name,
                        r.side as side,
                        sum(r.won) as round_wins,
                        count(r.won) - sum(r.won) as round_losses,
                        count(r.won) as round_played,
                        p.pistol_round_wins,
                        p.pistol_round_losses,
                        p.pistol_round_played,
                        m2.matches_won,
                        m2.matches_tied,
                        m2.matches_lost,
                        m2.win_percent,
                        m2.not_lose_percent,
                        (m2.matches_won + m2.matches_tied + m2.matches_lost) as matches_played
                    from tb_csgo_round r
                    join tb_csgo_match m
                    on m.pk_csgo_match_id = r.fk_pk_csgo_match_id
                    join (
                         select
                             map,
                             sum(ir.won) as pistol_round_wins,
                             count(ir.won) - sum(ir.won) as pistol_round_losses,
                             count(ir.won) as pistol_round_played
                        from tb_csgo_round ir
                        join tb_csgo_match im
                        on im.pk_csgo_match_id = ir.fk_pk_csgo_match_id
                        where round_number in (1,16)
                        and ir.side = 'CT' " . ($teamSize == null ? '' : "and im.team_size = :size") . "
                        group by im.map
                    ) p
                    on m.map = p.map
                    join (
                        select
                            map,
                            sum(won) as matches_won,
                            count(won) - sum(won) - sum(tied) as matches_lost,
                            sum(tied) matches_tied,
                            round((sum(won)+sum(tied))/count(won)*100) as not_lose_percent,
                            round((sum(won))/count(won)*100) as win_percent
                        from tb_csgo_match im2
                        " . ($teamSize == null ? '' : "where im2.team_size = :size") . "
                        group by map
                    ) m2
                    on m.map = m2.map
                    where r.side = 'CT' " . ($teamSize == null ? '' : "and m.team_size = :size") . "
                    group by m.map
                )
                order by win_percent desc, not_lose_percent desc, matches_tied asc;";
        $params = array();
        if (!(is_null($teamSize))) {
            $params[':size'] = $teamSize;
        }
        return parent::fetchAll($sql, $params);
    }

    public static function getMatchHistory($teamSize = null) {
        $sql = "select
                    m.team_size as players,
                    m.won,
                    m.map,
                    sum(r.won) as rounds_won,
                    max(r.round_number) - sum(r.won) as rounds_lost
                from tb_csgo_match m
                join tb_csgo_round r
                on m.pk_csgo_match_id = r.fk_pk_csgo_match_id "
                . (!(is_null($teamSize)) ? " where team_size = :size " : "") .
                "group by m.pk_csgo_match_id;";
        $params = array();
        if (!(is_null($teamSize))) {
            $params[':size'] = $teamSize;
        }
        return parent::fetchAll($sql, $params);
    }

    public static function getSmokes() {

    }

}