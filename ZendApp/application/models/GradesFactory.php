<?php

class GradesFactory extends ModelFactory {

    /**
     * Gets all assessments for a user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get data for
     *
     * @return array(assessments) Array of assessments for this user
     */
    private static function getUserAssessments($userId) {
        $sql = "SELECT
                    a.pk_grades_assessment_id AS id,
                    a.name,
                    a.weight,
                    a.grade,
                    a.fk_pk_grades_module_id
                FROM tb_grades_module m
                JOIN tb_grades_assessment a
                ON m.pk_grades_module_id = a.fk_pk_grades_module_id
                WHERE m.fk_pk_user_id = :userId
                AND a.is_active = 1";
        $params = array(
            ":userId" => $userId
        );

        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets all modules for a user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get data for
     *
     * @return array(modules) Array of modules for this user
     */
    private static function getUserModules($userId) {
        $sql = "SELECT
                    gm.pk_grades_module_id,
                    gm.name,
                    gm.code,
                    gm.yearOfStudy as year,
                    gm.credits,
                    gm.url,
                    gs.name as semester,
                    gs.pk_grades_semester_id as semester_id
                FROM tb_grades_module gm
                JOIN tb_grades_semester gs
                ON gm.fk_pk_grades_semester_id = gs.pk_grades_semester_id
                WHERE fk_pk_user_id = :userId
                AND gm.is_active = 1
                ORDER BY gs.pk_grades_semester_id";
        $params = array(
            ":userId" => $userId
        );

        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets all modules, and their grades, for a user
     *
     * @author Craig Knott
     *
     * @param int  $userId            Id of user to get data for
     * @param bool $includeModuleCode Whether or not to also include the module code in the results
     *
     * @return array(modules) Array of modules (and their grades) for this user
     */
    public static function getUserModuleGrades($userId, $includeModuleCode = false) {
        $sql = "SELECT
                     t.pk_grades_module_id as id,
                     tgm.code,
                     t.grade
                from (
                    select
                        pk_grades_module_id,
                        round(cast(((sum(grade * weight))/100) as decimal)) as grade,
                        cast(((sum(grade * weight))/100) as decimal) as raw
                    from tb_grades_assessment tga
                    join tb_grades_module tgm
                    on tga.fk_pk_grades_module_id = tgm.pk_grades_module_id
                    where fk_pk_user_id = :userId
                    and tga.is_active = 1
                    group by pk_grades_module_id
                    order by round(cast(((sum(grade * weight))/100) as decimal))
                ) t
                join tb_grades_module tgm
                on t.pk_grades_module_id = tgm.pk_grades_module_id;";
        $params = array(
            ":userId" => $userId
        );
        $results = parent::fetchAll($sql, $params);

        if ($includeModuleCode) {
            return $results;
        }

        $grades = array();
        foreach ($results as $result) {
            $grades[$result->id] = $result->grade;
        }

        return $grades;
    }

    /**
     * Get all years, and their grades, for a user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get data for
     *
     * @return array(year) Array of years (and their grades) for this user
     */
    private static function getYearGrades($userId) {
        $sql = "SELECT
                    t2.yearOfStudy,
                    sum(t2.weightedGrade/t2.sumOfCredits) as grade,
                    round(sum(t2.weightedGrade/t2.sumOfCredits), 2) as rounded
                FROM (
                    SELECT
                        t.pk_grades_module_id as id,
                        t.grade as moduleGrade,
                        tgm.yearOfStudy,
                        tgm.credits,
                        (t.grade * tgm.credits) as weightedGrade,
                        (
                            select
                                sum(credits)
                            from tb_grades_module
                            where fk_pk_user_id = :userId
                            and is_active = 1
                            group by yearOfStudy
                            having(yearOfStudy) = tgm.yearOfStudy
                        ) as sumOfCredits
                    from (
                        SELECT
                            pk_grades_module_id,
                            round(cast(((sum(grade * weight))/100) as decimal)) as grade,
                            cast(((sum(grade * weight))/100) as decimal) as raw
                        from tb_grades_assessment tga
                        join tb_grades_module tgm
                        on tga.fk_pk_grades_module_id = tgm.pk_grades_module_id
                        where fk_pk_user_id = :userId
                        and tga.is_active = 1
                        group by pk_grades_module_id
                        order by round(cast(((sum(grade * weight))/100) as decimal))
                    ) t
                    join tb_grades_module tgm
                    on t.pk_grades_module_id = tgm.pk_grades_module_id
                    where tgm.is_active = 1
                ) t2
                group by t2.yearOfStudy";
        $params = array(
            ":userId" => $userId
        );
        $results = parent::fetchAll($sql, $params);

        $grades = array();
        foreach ($results as $result) {
            $grades[$result->yearOfStudy] = $result->rounded;
        }

        return $grades;
    }

    /**
     * Get all modules, and their details, for a user, broken down by year
     *
     * @author Craig Knott
     *
     * @param int $userId    Id of user to get data for
     * @param int $yearCount How many years this user's course is
     *
     * @return array(years) Array of years (and their modules, and details) for this user
     */
    public static function getModuleDetails($userId, $yearCount) {
        // Get all modules for user
        $modules = self::getUserModules($userId);
        // Get all module assessments for user
        $assessments = self::getUserAssessments($userId);
        // Get module grades for user
        $grades = self::getUserModuleGrades($userId);
        // Get weight of each year
        $yearCredits = self::getYearWeights($userId, true);
        // Get overall grade of each year
        $yearGrades = self::getYearGrades($userId);

        // Add assessments and grades to the modules
        foreach ($modules as $module) {
            $module->grade = 0;
            if (array_key_exists($module->pk_grades_module_id, $grades)) {
                $module->grade = $grades[$module->pk_grades_module_id];
            }

            $moduleAssessments = array_filter(
                $assessments,
                function ($e) use ($module) {
                    return ($e->fk_pk_grades_module_id == $module->pk_grades_module_id);
                }
            );
            $module->assessments = $moduleAssessments;
        }

        // Create array of years, and populate with an array for each semester
        $years = array();
        for ($i = 1; $i <= $yearCount; $i++) {
            $yearGrade = array_key_exists($i, $yearGrades) ? $yearGrades[$i] : 0;

            $years[$i] = array(
                'semesters' => array(
                    'Autumn'    => array(),
                    'Spring'    => array(),
                    'Full Year' => array()
                ),
                'credits'   => $yearCredits[$i]->credits,
                'yearGrade' => $yearGrade,
                'wGrade'    => (($yearCredits[$i]->credits * $yearGrade) / 100)
            );
        }

        // Add modules to their correct year/semester
        foreach ($modules as $module) {
            if (array_key_exists($module->year, $years)) {
                array_push($years[$module->year]['semesters'][$module->semester], $module);
            }
        }

        return $years;
    }

    /**
     * Get all years, and their weights, for a user
     *
     * @author Craig Knott
     *
     * @param int  $userId      Id of user to get data for
     * @param bool $indexByYear Flags whether or not the results should be as they are, or indexed by year
     *
     * @return array(years) Array of years (and their weights) for this user
     */
    public static function getYearWeights($userId, $indexByYear = false) {
        $sql = "SELECT
                    pk_grades_year_id as id,
                    yearOfStudy,
                    weight
                from tb_grades_year
                where fk_pk_user_id = :userId";
        $params = array(
            ':userId' => $userId
        );
        $results = parent::fetchAll($sql, $params);

        if ($indexByYear) {
            // Change the ids of the array, to be the year numbers instead
            $newResults = array();
            foreach ($results as $result) {
                $newResults[$result->yearOfStudy] = (object)array(
                    'credits' => $result->weight
                );
            }
            $results = $newResults;
        }

        return $results;
    }

    /**
     * Add a new module for a user
     *
     * @author Craig Knott
     *
     * @param int    $userId   Id of user to get data for
     * @param string $code     The module code (G5XYYY)
     * @param string $name     The name of the module
     * @param int    $year     Year of study the module was taken in
     * @param int    $credits  Number of credits module is worth
     * @param int    $semester Id of the semester the module is in
     *
     * @return void
     */
    public static function addNewModule($userId, $code, $name, $year, $credits, $semester) {
        $sql = "INSERT INTO tb_grades_module (
                    name,
                    code,
                    yearOfStudy,
                    fk_pk_user_id,
                    credits,
                    fk_pk_grades_semester_id,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :name,
                    :code,
                    :year,
                    :userId,
                    :credits,
                    :semester,
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                )";
        $params = array(
            ":name"     => $name,
            ":code"     => $code,
            ":year"     => $year,
            ":userId"   => $userId,
            ":credits"  => $credits,
            ":semester" => $semester
        );
        parent::execute($sql, $params);
    }

    /**
     * Add a new assessment to a module
     *
     * @author Craig Knott
     *
     * @param int    $moduleId  Id of the module to add to
     * @param string $name      The name of the assessment
     * @param int    $weight    The weight of the assessment
     * @param float  $mark      Mark received for the assessment
     * @param int    $createdBy Who created this assessment
     *
     * @return void
     */
    public static function addNewAssessment($moduleId, $name, $weight, $mark, $createdBy) {
        $sql = "INSERT INTO tb_grades_assessment (
                    fk_pk_grades_module_id,
                    name,
                    weight,
                    grade,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :module,
                    :name,
                    :weight,
                    :grade,
                    :createdBy,
                    NOW(),
                    :createdBy,
                    NOW()
                )";
        $params = array(
            ":module"    => $moduleId,
            ":name"      => $name,
            ":weight"    => $weight,
            ":grade"     => $mark,
            ":createdBy" => $createdBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Change the active status of the given module
     *
     * @author Craig Knott
     *
     * @param int $moduleId  The id of the module to modify
     * @param int $isActive  Whether or not the module is active (1) or not (0)
     * @param int $updatedBy Who updated this module
     *
     * @return void
     */
    public static function setModuleActive($moduleId, $isActive, $updatedBy) {
        $sql = "UPDATE tb_grades_module
                SET is_active = :isActive,
                    datetime_updated = NOW(),
                    updated_by = :updatedBy
                WHERE pk_grades_module_id = :moduleId";
        $params = array(
            ':moduleId'  => $moduleId,
            ':isActive'  => $isActive,
            ':updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Updates the details of the given module
     *
     * @author Craig Knott
     *
     * @param int    $moduleId  The id of the module to modify
     * @param string $code      The module's new module code
     * @param int    $credits   The module's new credit value
     * @param string $name      The module's new name
     * @param int    $year      The module's new year of study
     * @param int    $semester  The module's new semester of study
     * @param int    $updatedBy Who updated this row
     *
     * @return void
     */
    public static function updateModuleDetails($moduleId, $code, $credits, $name, $year, $semester, $updatedBy) {
        $sql = "UPDATE tb_grades_module
                SET code = :code,
                    name = :name,
                    credits = :credits,
                    yearOfStudy = :year,
                    fk_pk_grades_semester_id = :semester,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_grades_module_id = :moduleId";
        $params = array(
            ':moduleId'  => $moduleId,
            ':code'      => $code,
            ':credits'   => $credits,
            ':name'      => $name,
            ':year'      => $year,
            ':semester'  => $semester,
            ':updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Updates the weights of the given years
     *
     * @author Craig Knott
     *
     *
     * @param array(int, int) $weightsArray Array of year id's, and their new weights
     * @param int             $updatedBy    Who updated this row
     *
     * @return void
     */
    public static function updateYearWeight($weightsArray, $updatedBy) {
        foreach ($weightsArray as $year) {
            $sql = "UPDATE tb_grades_year
                    SET weight = :weight,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                    WHERE pk_grades_year_id = :yearId";
            $params = array(
                ':weight'    => $year['weight'],
                ':yearId'    => $year['id'],
                ':updatedBy' => $updatedBy
            );
            parent::execute($sql, $params);
        }
    }

    /**
     * Change the active status of the given assessment
     *
     * @author Craig Knott
     *
     * @param int $assessmentId Assessment to modify
     * @param int $isActive     Whether or not the module is active (1) or not (0)
     * @param int $updatedBy    Who updated this row
     *
     * @return void
     */
    public static function setAssessmentActive($assessmentId, $isActive, $updatedBy) {
        $sql = "UPDATE tb_grades_assessment
                SET is_active = :isActive,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_grades_assessment_id = :assessmentId";
        $params = array(
            ':assessmentId' => $assessmentId,
            ':isActive'     => $isActive,
            ':updatedBy'    => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Update the details of a specific assessment
     *
     * @author Craig Knott
     *
     * @param int $assessmentId The id of the assessment to modify
     * @param int $name         The new name of the assessment
     * @param int $weight       The new weight of the assessment
     * @param int $grade        The new grade of the assessment
     * @param int $updatedBy    Who updated this row
     *
     * @return void
     */
    public static function editAssessment($assessmentId, $name, $weight, $grade, $updatedBy) {
        $sql = "UPDATE tb_grades_assessment
                SET name = :name,
                    weight = :weight,
                    grade = :grade,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_grades_assessment_id = :assessmentId";
        $params = array(
            ':assessmentId' => $assessmentId,
            ':name'         => $name,
            ':weight'       => $weight,
            ':grade'        => $grade,
            ':updatedBy'    => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Add a users years to the database, with their associated weights
     *
     * @author Craig Knott
     *
     * @param int             $userId      Id of user to get data for
     * @param array(int, int) $yearWeights Array of year id's, and their assigned weights
     *
     * @return void
     */
    public static function addYearWeights($userId, $yearWeights) {
        $sql = "INSERT INTO tb_grades_year (
                    fk_pk_user_id,
                    yearOfStudy,
                    weight,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES ";
        foreach ($yearWeights as $yearWeight) {
            $sql .= "(" . $userId . ", "
                . $yearWeight['year'] . ", "
                . $yearWeight['weight'] . ", "
                . $userId . ", "
                . "NOW(), "
                . $userId . ", "
                . "NOW(), "
                . "),";
        }
        $sql = rtrim($sql, ",");

        parent::execute($sql);
    }
}