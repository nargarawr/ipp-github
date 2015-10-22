<?php

class RevisionFactory extends ModelFactory {

    /**
     * Get all revision periods for a user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get revision periods for
     *
     * @return array(revision period) Revision period id, with name and date range
     */
    public static function getRevisionPeriods($userId) {
        $sql = "SELECT
                    pk_revision_period_id AS id,
                    name,
                    start_date,
                    end_date
                FROM tb_revision_period
                WHERE fk_pk_user_id = :userId
                AND is_active = 1";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get a revision period by it's id
     *
     * @author Craig Knott
     *
     * @param int $pid Id of the period to get
     *
     * @return object Object of period details (id, start date, end date)
     */
    public static function getPeriodById($pid) {
        $sql = "SELECT
                    pk_revision_period_id as id,
                    start_date,
                    end_date
                FROM tb_revision_period
                WHERE pk_revision_period_id = :pid";
        $params = array(
            ":pid" => $pid
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Get the revision period the user is currently in (based on today's date)
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to check
     *
     * @return object Object of period id, start date, and end date
     */
    public static function getCurrentRevisionPeriod($userId) {
        $sql = "SELECT
                    pk_revision_period_id as id,
                    start_date,
                    end_date
                FROM tb_revision_period
                WHERE pk_revision_period_id IN (
                    SELECT
                        number_value
                    FROM tb_user_setting
                    WHERE fk_pk_setting_id = 4 -- latest_period setting
                    AND fk_pk_user_id = :userId
                );";
        $params = array(
            ":userId" => $userId
        );
        $results = parent::fetchOne($sql, $params);

        if ($results !== false) {
            return $results;
        }

        $sql = "SELECT
                    pk_revision_period_id as id,
                    start_date,
                    end_date
                FROM tb_revision_period
                WHERE start_date < now()
                AND end_date > now()
                AND is_active = 1
                AND fk_pk_user_id = :userId;
                ORDER BY start_date ASC
                LIMIT 1;";
        $params = array(
            ":userId" => $userId
        );
        $results = parent::fetchOne($sql, $params);

        if ($results !== false) {
            return $results;
        }

        $sql = "SELECT
                    pk_revision_period_id as id,
                    start_date,
                    end_date
                FROM tb_revision_period
                WHERE is_active = 1
                AND fk_pk_user_id = :userId
                ORDER BY start_date ASC
                LIMIT 1;";
        return parent::fetchOne($sql, $params);
    }

    /**
     * Get all exams for a given user and period
     *
     * @author Craig Knott
     *
     * @param int $userId    Id of the user to get exams for
     * @param int $period_id Id of the period of exams
     *
     * @return array(exam) Array of exams for this user for this period
     */
    public static function getUserExams($userId, $period_id) {
        $sql = "SELECT
                    pk_revision_exam_id as id,
                    module,
                    worth,
                    location,
                    date,
                    time,
                    length,
                    seat
                FROM tb_revision_exam
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                AND fk_pk_revision_period_id = :period_id
                ORDER BY date, time";
        $params = array(
            ':userId'    => $userId,
            ':period_id' => $period_id
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Add a new exam for the user and period
     *
     * @author Craig Knott
     *
     * @param int    $userId   The id of the user to add this exam to
     * @param string $module   Name of module this exam belongs to
     * @param float  $worth    Worth of this exam
     * @param string $location Location of this exam
     * @param string $date     The date of the exam
     * @param string $time     The start time of the exam
     * @param float  $length   The length of the exam in hours
     * @param string $seat     The seat number/letter
     * @param int    $period   Id of the exam period this
     *
     * @return void
     */
    public static function addExam($userId, $module, $worth, $location, $date, $time, $length, $seat, $period) {
        $sql = "INSERT INTO tb_revision_exam (
                    fk_pk_user_id,
                    module,
                    worth,
                    location,
                    date,
                    time,
                    length,
                    seat,
                    is_active,
                    fk_pk_revision_period_id,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :userId,
                    :module,
                    :worth,
                    :location,
                    :date,
                    :time,
                    :length,
                    :seat,
                    1,
                    :period,
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                )";
        $params = array(
            ':userId'   => $userId,
            ':module'   => $module,
            ':worth'    => $worth,
            ':location' => $location,
            ':date'     => $date,
            ':time'     => $time,
            ':length'   => $length,
            ':seat'     => $seat,
            ':period'   => $period
        );
        parent::execute($sql, $params);
    }

    /**
     * Edit the specified exam
     *
     * @author Craig Knott
     *
     * @param int    $userId   The id of the user to add this exam to
     * @param string $module   Name of module this exam belongs to
     * @param float  $worth    Worth of this exam
     * @param string $location Location of this exam
     * @param string $date     The date of the exam
     * @param string $time     The start time of the exam
     * @param float  $length   The length of the exam in hours
     * @param string $seat     The seat number/letter
     * @param int    $examId   Id of the exam to edit
     *
     * @return void
     */
    public static function editExam($userId, $module, $worth, $location, $date, $time, $length, $seat, $examId) {
        $sql = "UPDATE tb_revision_exam
                SET module = :module,
                    worth = :worth,
                    location = :location,
                    date = :date,
                    time = :time,
                    length = :length,
                    seat = :seat,
                    updated_by = :userId,
                    datetime_updated = NOW()
                WHERE fk_pk_user_id = :userId
                AND pk_revision_exam_id = :examId";
        $params = array(
            ':userId'   => $userId,
            ':examId'   => $examId,
            ':module'   => $module,
            ':worth'    => $worth,
            ':location' => $location,
            ':date'     => $date,
            ':time'     => $time,
            ':length'   => $length,
            ':seat'     => $seat
        );
        parent::execute($sql, $params);
    }

    /**
     * Delete the specified exam
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user to get the information for
     * @param int $examId Id of the exam to remove
     *
     * @return void
     */
    public static function removeExam($userId, $examId) {
        $sql = "UPDATE tb_revision_exam
                SET is_active = 0,
                    updated_by = :userId,
                    datetime_updated = NOW()
                WHERE fk_pk_user_id = :userId
                AND pk_revision_exam_id = :examId";
        $params = array(
            ':userId' => $userId,
            ':examId' => $examId
        );
        parent::execute($sql, $params);
    }

    /**
     * Gets the number of exams a user has for this period
     *
     * @author Craig Knott
     *
     * @param int $userId , id of the user to check for
     * @param int $pid    , revision period id to check in
     *
     * @return int, how many exams this user has for the given period
     */
    public static function getUserExamCount($userId, $pid) {
        $sql = "SELECT
                    count(fk_pk_revision_period_id) as number
                FROM tb_revision_exam
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                AND fk_pk_revision_period_id = :periodId
                GROUP BY fk_pk_revision_period_id";
        $params = array(
            'userId'   => $userId,
            'periodId' => $pid
        );
        return parent::fetchOne($sql, $params)->number;
    }

    /**
     * Get all actual and planned revision for a revision period
     *
     * @param int $revisionPeriodId , revision period to get the data for
     * @param int $userId           , user to get the data for
     *
     * @return array, date indexed list of exams with their actual/planned revision
     */
    public static function getPeriodRevisionEntries($revisionPeriodId, $userId) {
        $sql = "SELECT
                    ree.date as date,
                    re.pk_revision_exam_id as examId,
                    ree.planned_revision as planned,
                    ree.actual_revision as actual
                from tb_revision_exam_entry ree
                join tb_revision_exam re
                on re.pk_revision_exam_id = ree.fk_revision_exam_id
                join tb_revision_period rp
                on re.fk_pk_revision_period_id = rp.pk_revision_period_id
                where rp.pk_revision_period_id = :revisionPeriodId
                and rp.fk_pk_user_id = :userId
                and ree.date >= rp.start_date
                and ree.date <= rp.end_date
                and re.is_active = 1";
        $params = array(
            'revisionPeriodId' => $revisionPeriodId,
            'userId'           => $userId
        );

        $results = parent::fetchAll($sql, $params);

        // Convert the results to be date-indexed
        $processedResults = array();
        foreach ($results as $result) {
            if (array_key_exists($result->date, $processedResults)) {
                $processedResults[$result->date][] = $result;
            } else {
                $processedResults[$result->date] = array(
                    $result
                );
            }
        }

        return $processedResults;
    }

    /**
     * Delete a specified exam period
     *
     * @author Craig Knott
     *
     * @param int $id        Id of exam period to delete
     * @param int $updatedBy Who updated this row
     *
     * @return void
     */
    public static function deleteExamPeriod($id, $updatedBy) {
        $sql = "UPDATE tb_revision_period
                SET is_active = 0,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_revision_period_id = :id";
        $params = array(
            ":id"        => $id,
            ':updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Edit the details of a specified exam period
     *
     * @author Craig Knott
     *
     * @param int    $id         Id of the period to edit
     * @param string $name       Name of this period
     * @param string $start_date Start date of this period (YYYY-MM-DD)
     * @param string $end_date   End date of this period (YYYY-MM-DD)
     * @param int    $updatedBy  Who updated this row
     *
     * @return void
     */
    public static function editExamPeriod($id, $name, $start_date, $end_date, $updatedBy) {
        $sql = "UPDATE tb_revision_period
                SET name = :name,
                    start_date = :start_date,
                    end_date = :end_date,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_revision_period_id = :id";
        $params = array(
            ":id"         => $id,
            ":name"       => $name,
            ":start_date" => $start_date,
            ":end_date"   => $end_date,
            ':updatedBy'  => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Add an exam period for a user
     *
     * @author Craig Knott
     *
     * @param int    $userId    Id of the user to add to
     * @param string $name      Name of this period
     * @param string $startDate Start date of this period (YYYY-MM-DD)
     * @param string $endDate   End date of this period (YYYY-MM-DD)
     *
     * @return void
     */
    public static function addExamPeriod($userId, $name, $startDate, $endDate) {
        $sql = "INSERT INTO tb_revision_period (
                    fk_pk_user_id,
                    name,
                    start_date,
                    end_date,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :userId,
                    :name,
                    :startDate,
                    :endDate,
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                );";
        $params = array(
            ":userId"    => $userId,
            ":name"      => $name,
            ":startDate" => $startDate,
            ":endDate"   => $endDate
        );
        parent::execute($sql, $params);
    }

    /**
     * Get a collection of different statistics on a given revision period
     *
     * @param int $revisionPeriodId , id of the revision period to look at stats for
     *
     * @return array, array of stats
     */
    public static function getStatsForPeriod($revisionPeriodId) {
        $sql = "select
                    sum(planned_revision) as sumPlanned,
                    sum(actual_revision) as sumActual,
                    100 * (sum(actual_revision)/sum(planned_revision)) as percentComplete,
                    CASE WHEN NOW() < end_date
                        THEN sum(actual_revision)/DATEDIFF(NOW(), start_date)
                        ELSE sum(actual_revision)/DATEDIFF(end_date, start_date)
                    END as averagePerDay,
                    (
                        select
                            DATEDIFF(tre.date, NOW())
                        from tb_revision_exam tre
                            join tb_revision_period trp
                                on trp.pk_revision_period_id = tre.fk_pk_revision_period_id
                        where trp.pk_revision_period_id = :periodId
                              and tre.is_active = 1
                              and tre.date > NOW()
                        order by tre.date
                        limit 1
                    ) as nextExamIn
                from tb_revision_exam_entry tree
                join tb_revision_exam tre
                on tree.fk_revision_exam_id = tre.pk_revision_exam_id
                join tb_revision_period trp
                on trp.pk_revision_period_id = tre.fk_pk_revision_period_id
                where tre.fk_pk_revision_period_id = :periodId
                and tre.fk_pk_user_id = 1
                and tre.is_active = 1;";
        $params = array(
            'periodId' => $revisionPeriodId
        );
        $result = parent::fetchOne($sql, $params);

        $stats = array(
            (object)array(
                'name'  => 'Total Revision Completed',
                'value' => number_format($result->sumActual, 2) . ' hours'
            ),
            (object)array(
                'name'  => 'Total Revision Planned',
                'value' => number_format($result->sumPlanned, 2) . ' hours'
            ),
            (object)array(
                'name'  => 'Percentage Complete',
                'value' => number_format($result->percentComplete, 2) . '%'
            ),
            (object)array(
                'name'  => 'Average Revision Per Day',
                'value' => number_format($result->averagePerDay, 2) . ' hours'
            )
        );
        if (!is_null($result->nextExamIn)) {
            $stats[] = (object)array(
                'name'  => 'Next Exam In',
                'value' => $result->nextExamIn . ' days'
            );
        }
        return $stats;
    }

    public static function updateRevisionEntry($date, $examId, $planned, $actual, $userId) {
        $sql = "insert into tb_revision_exam_entry (
                    fk_revision_exam_id,
                    date,
                    planned_revision,
                    actual_revision,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) values (
                    :examId,
                    :date,
                    :actual_revision,
                    :planned_revision,
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                ) ON DUPLICATE KEY UPDATE
                    actual_revision = :actual_revision,
                    planned_revision = :planned_revision,
                    updated_by = :userId,
                    datetime_updated = NOW()";
        $params = array(
            'date'             => $date,
            'examId'           => $examId,
            'actual_revision'  => $actual,
            'planned_revision' => $planned,
            'userId'           => $userId
        );
        parent::execute($sql, $params);
    }
}
