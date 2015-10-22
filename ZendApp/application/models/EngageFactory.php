<?php

class EngageFactory extends ModelFactory {

    /**
     * Get all (non suppressed) engagement apps from the database
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user, to check for suppressions
     *
     * @return array(apps) Array of all non-suppressed apps for this user
     */
    public static function getAllEngagementApps($userId) {
        $sql = "SELECT
                    name,
                    shortDesc,
                    url
                FROM tb_app a
                WHERE pk_app_id NOT IN (
                    SELECT
                        fk_pk_app_id
                    FROM tb_user_app_suppression
                    WHERE fk_pk_user_id = :userId
                    AND is_active = 1
                )
                AND a.is_active = 1";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all engagement apps from the database (ignoring suppressions)
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user, to check for suppressions
     *
     * @return array(apps) Array of all apps for this user (and their suppressed status)
     */
    public static function getAllEngagementAppsIgnoringSuppressions($userId) {
        $sql = "SELECT
                    pk_app_id,
                    url,
                    has_settings,
                    name,
                    is_active,
                    IFNULL((
                        SELECT 1
                        FROM tb_user_app_suppression
                        WHERE pk_app_id = fk_pk_app_id
                        AND fk_pk_user_id = :userId
                        AND is_active = 1), 0
                    ) AS suppressed
                FROM tb_app;";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all apps that have settings
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user, to check for suppressions
     *
     * @return array(apps) Array of all apps with settings
     */
    public static function getAllAppsWithSettings($userId) {
        $sql = "SELECT
                    url,
                    name,
                    has_settings
                FROM tb_app
                WHERE pk_app_id NOT IN (
                    SELECT
                        fk_pk_app_id
                    FROM tb_user_app_suppression
                    WHERE fk_pk_user_id = :userId
                    AND is_active = 1
                )
                AND has_settings = 1";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

}