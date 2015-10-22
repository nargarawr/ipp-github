<?

class SutonargFactory extends ModelFactory {

    public static function getSutonargGameshow() {
        // Get all episodes
        $episodesSql = "SELECT
                            pk_gameshow_episode_id as episode_id,
                            datetime,
                            season_number,
                            episode_number,
                            is_finale,
                            winner_id,
                            video_id
                        FROM tb_gameshow_episode";
        $episodes = parent::fetchAll($episodesSql);

        // Get players with their episodes
        $participantsSql = "SELECT
                                pk_gameshow_participant_id as player_id,
                                name,
                                fk_pk_gameshow_episode_id as episode_id
                            FROM tb_gameshow_participant tgp
                            JOIN tb_gameshow_participant_episode tgpe
                            ON tgpe.fk_pk_gameshow_participant_id = tgp.pk_gameshow_participant_id";
        $participants = parent::fetchAll($participantsSql);

        // Add participants to episodes
        foreach ($episodes as $episode) {
            $episode->participants = array_filter($participants, function ($participant) use ($episode) {
                return $participant->episode_id === $episode->episode_id;
            });
        }

        // Get all challenges details and episode they feature on
        $challengesSql = "SELECT
                              pk_gameshow_challenge_id as challenge_id,
                              tgc.name as challenge_name,
                              fk_pk_gameshow_episode_id as episode_id,
                              icon_url
                          FROM tb_gameshow_challenge tgc
                          JOIN tb_gameshow_episode_challenge tgec
                          ON tgec.fk_pk_gameshow_challenge_id = tgc.pk_gameshow_challenge_id
                          JOIN tb_gameshow_challenge_type tgct
                          ON tgct.pk_gameshow_challenge_type_id = tgc.fk_pk_gameshow_challenge_type_id
                          JOIN tb_gameshow_hub tgh
                          ON tgc.fk_pk_gameshow_hub_id = tgh.pk_gameshow_hub_id
                          ORDER BY fk_pk_gameshow_episode_id, pk_gameshow_challenge_id";
        $challenges = parent::fetchAll($challengesSql);

        // Put the challenges into the episodes
        foreach ($episodes as $episode) {
            $episode->challenges = array_filter($challenges, function ($challenge) use ($episode) {
                return $challenge->episode_id == $episode->episode_id;
            });
        }

        $seasons = array();
        foreach ($episodes as $episode) {
            if (array_key_exists($episode->season_number, $seasons)) {
                $seasons[$episode->season_number]['episodes'][] = $episode;
            } else {
                $seasons[$episode->season_number]['episodes'] = array(
                    $episode
                );
            }
        }

        return $seasons;
    }

    public static function getMostPlayedChallenges() {
        $sql = "SELECT
                    name,
                    count(name) as plays
                FROM tb_gameshow_episode_challenge tgec
                JOIN tb_gameshow_challenge tgc
                ON tgec.fk_pk_gameshow_challenge_id = tgc.pk_gameshow_challenge_id
                GROUP BY fk_pk_gameshow_challenge_id
                ORDER BY count(name) desc";
        return parent::fetchAll($sql);
    }

    public static function getPlayers() {
        $sql = "SELECT
                    pk_gameshow_participant_id as player_id,
                    name
                FROM tb_gameshow_participant";
        $players = parent::fetchAll($sql);

        $appearancesSql = "SELECT
                               fk_pk_gameshow_participant_id as player_id,
                               season_number,
                               episode_number,
                               winner_id,
                               is_finale
                           FROM tb_gameshow_participant_episode tgpe
                           JOIN tb_gameshow_episode tge
                           ON tge.pk_gameshow_episode_id = tgpe.fk_pk_gameshow_episode_id";
        $appearances = parent::fetchAll($appearancesSql);

        foreach ($players as $player) {
            $player->appearances = array_filter($appearances, function ($appearance) use ($player) {
                return $player->player_id === $appearance->player_id;
            });
        }

        return $players;
    }

    public static function getWinners() {
        $sql = "select
                    tgp.name as name,
                    count(tgp.name) as wins
                from tb_gameshow_episode tge
                join tb_gameshow_participant_episode tgpe
                on tge.pk_gameshow_episode_id = tgpe.fk_pk_gameshow_episode_id
                join tb_gameshow_participant tgp
                on tgpe.fk_pk_gameshow_participant_id = tgp.pk_gameshow_participant_id
                where tge.winner_id = tgpe.fk_pk_gameshow_participant_id
                group by tge.winner_id
                order by count(tgp.name) desc";
        return parent::fetchAll($sql);
    }
}
