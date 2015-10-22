<?php

class FitnessFactory extends ModelFactory {

    /**
     * Returns all exercises, along with their muscles, and body parts
     *
     * @author Craig Knott
     *
     * @return array(exercises) Array of exercises, with their muscles and body parts
     */
    private static function getExercisesMusclesAndBodyparts() {
        $sql = "select
                    fe.pk_fitness_exercise_id as exerciseId,
                    fe.name as exerciseName,
                    fm.pk_fitness_muscle_id as muscleId,
                    fm.name as muscleName,
                    fb.name as bodypartName,
                    fb.pk_fitness_bodypart_id as bodypartId
                from tb_fitness_exercise_muscle fem
                join tb_fitness_exercise fe
                on fem.fk_pk_fitness_exercise_id = fe.pk_fitness_exercise_id
                join tb_fitness_muscle fm
                on fem.fk_pk_fitness_muscle_id = fm.pk_fitness_muscle_id
                join tb_fitness_bodypart fb
                on fm.fk_pk_fitness_bodypart_id = fb.pk_fitness_bodypart_id;";
        return self::fetchAll($sql);
    }

    /**
     * Returns a list of body parts, with sub arrays of muscles and exercises
     *
     * @author Craig Knott
     *
     * @return array(exercises) Array of body parts, with sub arrays of muscles and exercises
     */
    public static function getAllExercisesByBodypartAndMuscle() {
        $results = self::getExercisesMusclesAndBodyparts();
        $bodyparts = array();

        // First loop to get unique body parts out
        foreach ($results as $result) {
            if (!(array_key_exists($result->bodypartId, $bodyparts))) {
                $bodyparts[$result->bodypartId] = (object)array(
                    'id'      => $result->bodypartId,
                    'name'    => $result->bodypartName,
                    'muscles' => array()
                );
            }
        }

        // Second loop to add muscles to the body parts
        foreach ($results as $result) {
            if (!(array_key_exists($result->muscleId, $bodyparts[$result->bodypartId]->muscles))) {
                $bodyparts[$result->bodypartId]->muscles[$result->muscleId] = (object)array(
                    'id'        => $result->muscleId,
                    'name'      => $result->muscleName,
                    'exercises' => array()
                );
            }
        }

        // Final loop to add exercises to each muscle they belong to
        foreach ($results as $result) {
            $muscleId = $result->muscleId;
            $bodypartId = $result->bodypartId;
            $bodyparts[$bodypartId]->muscles[$muscleId]->exercises[] = (object)array(
                'id'   => $result->exerciseId,
                'name' => $result->exerciseName
            );
        }

        return $bodyparts;
    }

    /**
     * Get all exercises for a given workout
     *
     * @author Craig Knott
     *
     * @param int $workoutId Id of the workout to get information for
     *
     * @return array(exercises) Array of exercises for this workout
     */
    public static function getWorkoutExercises($workoutId) {
        $sql = "SELECT
                    pk_fitness_set_id as setId,
                    pk_fitness_exercise_id as exerciseId,
                    reps,
                    name,
                    weight
                FROM tb_fitness_set ft
                JOIN tb_fitness_exercise fe
                ON ft.fk_pk_fitness_exercise_id = fe.pk_fitness_exercise_id
                WHERE fk_pk_fitness_workout_id = :workoutId";
        $params = array(
            ':workoutId' => $workoutId
        );
        $results = self::fetchAll($sql, $params);

        $exercises = array();
        foreach ($results as $result) {
            if (!(array_key_exists($result->exerciseId, $exercises))) {
                $exercises[$result->exerciseId]['name'] = $result->name;
                $exercises[$result->exerciseId]['sets'] = array();
            }
            $exercises[$result->exerciseId]['sets'][] = $result;
        }

        return $exercises;
    }

    /**
     * Add a new workout for a user
     *
     * @author Craig Knott
     *
     * @param int $userId User to add this workout to
     *
     * @return int The id of the newly created workout
     */
    public static function createNewWorkout($userId) {
        $sql = "INSERT INTO tb_fitness_workout (
                    fk_pk_user_id,
                    datetime,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :userId,
                    NOW(),
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                )";
        $params = array(
            ':userId' => $userId
        );
        self::execute($sql, $params);

        $sql = "SELECT
                    pk_fitness_workout_id as id
                FROM tb_fitness_workout
                WHERE fk_pk_user_id = :userId
                ORDER BY pk_fitness_workout_id DESC
                LIMIT 1";
        $params = array(
            ':userId' => $userId
        );
        $result = self::fetchOne($sql, $params);
        return $result->id;
    }

    /**
     * Add a set to a workout
     *
     * @author Craig Knott
     *
     * @param int   $reps       Number of reps
     * @param float $weight     Weight of set
     * @param int   $workoutId  Id of the workout to add this set to
     * @param int   $exerciseId The id of the exercise being done
     * @param int   $createdBy  Who created this set
     *
     * @return void
     */
    public static function addSet($reps, $weight, $workoutId, $exerciseId, $createdBy) {
        $sql = "INSERT INTO tb_fitness_set (
                    fk_pk_fitness_exercise_id,
                    reps,
                    fk_pk_fitness_workout_id,
                    weight,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :exerciseId,
                    :reps,
                    :workoutId,
                    :weight,
                    :created_by,
                    NOW(),
                    :created_by,
                    NOW()
                )";
        $params = array(
            ':exerciseId' => $exerciseId,
            ':reps'       => $reps,
            ':workoutId'  => $workoutId,
            ':weight'     => $weight,
            'created_by'  => $createdBy
        );
        self::execute($sql, $params);
    }

    /**
     * Get the name of an exercise
     *
     * @author Craig Knott
     *
     * @param int $exerciseId Id of the exercise
     *
     * @return string Name of the exercise
     */
    public static function getExerciseName($exerciseId) {
        $sql = "SELECT
                    name
                FROM tb_fitness_exercise
                WHERE pk_fitness_exercise_id = :exerciseId";
        $params = array(
            ':exerciseId' => $exerciseId
        );
        return self::fetchOne($sql, $params)->name;
    }

    /**
     * Get the all sets for this exercise from the last workout this exercise was in
     *
     * @author Craig Knott
     *
     * @param int $exerciseId Id of the exercise
     * @param int $userId     Id of the user
     * @param int $workoutId  Id of the this workout (so we don't get the current workout's results)
     *
     * @return array(sets) Arrays of sets for this exercise from the last workout this exercise was in
     */
    public static function getLastWorkoutForExercise($exerciseId, $userId, $workoutId) {
        $sql = "SELECT
                    pk_fitness_set_id as id,
                    reps,
                    weight
                FROM tb_fitness_set tfs
                JOIN (
                    SELECT
                        pk_fitness_workout_id as id
                    FROM tb_fitness_set fs
                    JOIN tb_fitness_workout fw
                    ON fs.fk_pk_fitness_workout_id = fw.pk_fitness_workout_id
                    WHERE fk_pk_user_id = :userId
                    AND fk_pk_fitness_exercise_id = :exerciseId
                    AND pk_fitness_workout_id != :workoutId
                    ORDER BY datetime DESC
                    LIMIT 1
                ) as workout
                ON tfs.fk_pk_fitness_workout_id = workout.id
                WHERE fk_pk_fitness_exercise_id = :exerciseId";
        $params = array(
            ':exerciseId' => $exerciseId,
            ':userId'     => $userId,
            ':workoutId'  => $workoutId
        );
        return self::fetchAll($sql, $params);
    }

    /**
     * Get a list of all previous workouts for the user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user
     *
     * @return array(workouts) Array of previous workouts for the user
     */
    public static function getPreviousWorkouts($userId) {
        $sql = "SELECT
                    pk_fitness_workout_id as id,
                    datetime
                from tb_fitness_workout
                where fk_pk_user_id = :userId
                order by datetime desc";
        $params = array(
            ':userId' => $userId
        );
        return self::fetchAll($sql, $params);
    }

    /**
     * Returns a list of all muscles present in the database
     *
     * @author Craig Knott
     *
     * @return array(muscles) Array of muscles in the database
     */
    public static function getAllMuscles() {
        $sql = "SELECT
                    pk_fitness_muscle_id as id,
                    name
                FROM tb_fitness_muscle";
        return self::fetchAll($sql);
    }

}