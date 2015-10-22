<?php

class LiviFactory extends ModelFactory {

    /**
     * Gets all events for the calendar
     *
     * @author Craig Knott
     *
     * @return array(events) Array of all events for the calendar
     */
    public static function getAllEvents() {
        $sql = "SELECT
                    pk_livi_event_id as id,
                    day,
                    event,
                    is_active
                FROM tb_livi_event
                WHERE is_active = 1";
        return parent::fetchAll($sql);
    }

    /**
     * Removes an event from the calendar
     *
     * @author Craig Knott
     *
     * @param int $eventId   Id of the event to remove
     * @param int $updatedBy Who updated this row
     *
     * @return void
     */
    public static function removeEvent($eventId, $updatedBy) {
        $sql = "UPDATE tb_livi_event
                SET is_active = 0,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_livi_event_id = :id";
        $params = array(
            ':id'        => $eventId,
            ':updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Adds an event to the calendar
     *
     * @author Craig Knott
     *
     * @param string $day       Day of the event (YYYY-MM-DD format)
     * @param string $event     Description of the event
     * @param int    $createdBy Who created this row
     *
     * @return void
     */
    public static function addEvent($day, $event, $createdBy) {
        $sql = "INSERT INTO tb_livi_event (
                    day,
                    event,
                    is_active,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :day,
                    :event,
                    1,
                    :createdBy,
                    NOW(),
                    :createdBy,
                    NOW()
                );";
        $params = array(
            ':day'       => $day,
            ':event'     => $event,
            ':createdBy' => $createdBy
        );
        parent::execute($sql, $params);
    }
}