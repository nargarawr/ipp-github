<?php

class PurchaseFactory extends ModelFactory {

    /**
     * Returns a list of purchases for a user, based on various search parameters
     *
     * @author Craig Knott
     *
     * @param int        $userId     Id of user to get results for
     * @param array(int) $categories Array of category ids to include in search
     * @param string     $startDate  Start date for search, in format yyyy/mm/dd
     * @param string     $endDate    End date for search, in format yyyy/mm/dd
     * @param float      $minCost    Minimum cost for search
     * @param float      $maxCost    Maximum cost for search
     * @param string     $sortBy     Column to sort by
     * @param string     $sortByDir  Whether to sort ascending or descending
     *
     * @return array(purchase) Array of user purchases
     */
    public static function getAllPurchases($userId, $categories = null, $startDate = null, $endDate = null,
                                           $minCost = null, $maxCost = null, $sortBy = null, $sortByDir = null) {
        $sql = "SELECT
                    pk_purchase_purchase_id as id,
                    items,
                    cost,
                    tpc.name as category,
                    datetime_purchased
                FROM tb_purchase_purchase tp
                JOIN tb_purchase_category tpc
                ON tp.fk_pk_purchase_category_id = tpc.pk_purchase_category_id
                WHERE tp.fk_pk_user_id = :userId
                AND tp.is_active = 1";

        $params = array(
            'userId' => $userId
        );

        if (!(is_null($categories))) {
            $catSql = "\nAND tpc.pk_purchase_category_id in (";
            foreach ($categories as $category) {
                $catSql .= $category . ",";
            }
            $sql .= rtrim($catSql, ",") . ")";
        }

        if (!(is_null($startDate))) {
            $sql .= "\nAND datetime_purchased >= :startDate";
            $params['startDate'] = $startDate;
        }

        if (!(is_null($endDate))) {
            $sql .= "\nAND datetime_purchased <= :endDate";
            $params['endDate'] = $endDate;
        }

        if (!(is_null($minCost))) {
            $sql .= "\nAND cost >= :minCost";
            $params['minCost'] = $minCost;
        }

        if (!(is_null($maxCost))) {
            $sql .= "\nAND cost <= :maxCost";
            $params['maxCost'] = $maxCost;
        }

        if (!(is_null($sortBy))) {
            $sql .= "\nORDER BY " . $sortBy;
            if (!(is_null($sortByDir))) {
                $sql .= " " . $sortByDir;
            }
        }

        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets all custom purchase categories for a given user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(category) Array of user custom categories
     */
    public static function getCustomPurchaseCategories($userId) {
        $sql = "SELECT
                    pk_purchase_category_id as id,
                    name
                FROM tb_purchase_category
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                ORDER BY pk_purchase_category_id";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets all default purchase categories
     *
     * @author Craig Knott
     *
     * @return array(category) Array of default categories
     */
    public static function getDefaultPurchaseCategories() {
        $sql = "SELECT
                    pk_purchase_category_id as id,
                    name
                FROM tb_purchase_category
                WHERE fk_pk_user_id is NULL
                AND is_active = 1
                ORDER BY pk_purchase_category_id";
        return parent::fetchAll($sql);
    }

    /**
     * Gets all purchase categories for a given user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(category) Array of categories
     */
    public static function getPurchaseCategories($userId) {
        $sql = "SELECT
                    pk_purchase_category_id as id,
                    name
                FROM tb_purchase_category
                WHERE (fk_pk_user_id is NULL OR fk_pk_user_id = :userId)
                AND is_active = 1
                ORDER BY pk_purchase_category_id";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Deletes the given purchase, for the given user
     *
     * @author Craig Knott
     *
     * @param int $userId     Id of user who's purchase it is
     * @param int $purchaseId Id of purchase to remove
     *
     * @return void
     */
    public static function removePurchase($userId, $purchaseId) {
        $sql = "UPDATE tb_purchase_purchase
                SET is_active = 0,
                    updated_by = :userId,
                    datetime_updated = NOW()
                WHERE fk_pk_user_id = :userId
                AND pk_purchase_purchase_id = :purchaseId";
        $params = array(
            ':userId'     => $userId,
            ':purchaseId' => $purchaseId
        );
        parent::execute($sql, $params);
    }

    /**
     * Registers a purchase for the given user
     *
     * @author Craig Knott
     *
     * @param int    $userId   Id of user who's purchase it is
     * @param string $items    What item(s) were purchases
     * @param float  $cost     Cost of the purchased items
     * @param int    $category What category the purchase belongs to
     *
     * @return void
     */
    public static function addPurchase($userId, $items, $cost, $category) {
        $sql = "INSERT INTO tb_purchase_purchase (
                    fk_pk_user_id,
                    items,
                    cost,
                    fk_pk_purchase_category_id,
                    datetime_purchased,
                    is_active,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :userId,
                    :items,
                    :cost,
                    :category,
                    NOW(),
                    1,
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                );";
        $params = array(
            ':userId'   => $userId,
            ':items'    => $items,
            ':cost'     => $cost,
            ':category' => $category
        );
        parent::execute($sql, $params);
    }

    /**
     * Gets how much the user has spent, broken down by week. Returns empty string if the user has made no purchases
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(weeks) Array of weeks and their spending
     *       | string Empty string
     */
    private static function getSpendPerWeekByUserId($userId) {
        $firstPurchase = self::getFirstPurchase($userId);
        if ($firstPurchase === "") {
            return "";
        }
        $sql = "SELECT
                    date_beginning as beginning,
                    IFNULL(cost, 0) as cost
                FROM tb_week_beginning
                LEFT JOIN (
                    SELECT
                        sum(cost) as cost,
                        cast(date_sub(datetime_purchased, interval dayofweek(datetime_purchased)-1 day) as date) as weekBeginning
                    FROM tb_purchase_purchase
                    WHERE fk_pk_user_id = :userId
                    AND is_active = 1
                    GROUP BY weekBeginning
                ) AS spendPerWeek
                ON spendPerWeek.weekBeginning = date_beginning
                WHERE date_beginning <= CURDATE()
                AND date_beginning >= cast(date_sub(:firstPurchase, interval dayofweek(:firstPurchase)-1 day) as date);";
        $params = array(
            ':userId'        => $userId,
            ':firstPurchase' => $firstPurchase
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets how much the user has spent, broken down by month.
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(months) Array of months and their spending
     *       | string Empty string
     */
    private static function getSpendPerMonthByUserId($userId) {
        $firstPurchase = self::getFirstPurchase($userId);
        if ($firstPurchase === "") {
            return "";
        }
        $sql = "SELECT
                    CONCAT_WS('-', month(datetime_purchased), year(datetime_purchased)) as beginning,
                    month(datetime_purchased) as month,
                    year(datetime_purchased) as year,
                    sum(cost) as cost
                FROM tb_purchase_purchase
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                AND (
                    (YEAR(datetime_purchased) = YEAR(NOW()) AND MONTH(datetime_purchased) <= MONTH(NOW())) OR
                    (YEAR(datetime_purchased) < YEAR(NOW()))
                )
                GROUP BY MONTH(datetime_purchased), YEAR(datetime_purchased)
                ORDER BY year, month;";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets how much the user has spent, broken down by year.
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(years) Array of years and their spending
     *       | string Empty string
     */
    private static function getSpendPerYearByUserId($userId) {
        $firstPurchase = self::getFirstPurchase($userId);
        if ($firstPurchase === "") {
            return "";
        }
        $sql = "SELECT
                    year(datetime_purchased) as beginning,
                    sum(cost) as cost
                FROM tb_purchase_purchase
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                AND YEAR(datetime_purchased) <= YEAR(NOW())
                GROUP BY YEAR(datetime_purchased)
                ORDER BY beginning";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets how much the user has spent, broken down by the given time period. Returns null on error, or empty string
     * if no results
     *
     * @author Craig Knott
     *
     * @param int    $userId Id of user to get results for
     * @param string $period The period of which to group results (week, month, year)
     *
     * @return array(timePeriod) Array of time periods and their spending
     *       | string            Empty string
     *       | null
     */
    public static function getSpendPerPeriodByUserId($userId, $period) {
        if ($period === 'week') {
            return self::getSpendPerWeekByUserId($userId);
        } else {
            if ($period === 'month') {
                return self::getSpendPerMonthByUserId($userId);
            } else {
                if ($period === 'year') {
                    return self::getSpendPerYearByUserId($userId);
                }
            }
        }
        return null;
    }

    /**
     * Gets a user's total and average spend per week
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return object Spending aggregate data (average, total)
     */
    public static function getSpendingAggregatesByUserId($userId) {
        $sql = "SELECT
                    avg(byWeek.cost) as average,
                    sum(byWeek.cost) as total
                FROM (
                    SELECT
                        sum(cost) as cost,
                        cast(date_sub(datetime_purchased, interval dayofweek(datetime_purchased)-1 day) as date) as weekBeginning
                    FROM tb_purchase_purchase
                    WHERE fk_pk_user_id = :userId
                    AND is_active = 1
                    AND cast(date_sub(datetime_purchased, interval dayofweek(datetime_purchased)-1 day) as date) < now()
                    GROUP BY weekBeginning
                ) as byWeek;";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Gets a user's total spend for each category
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(category) Array of categories and their expenditure
     */
    public static function getTotalSpendingsByCategory($userId) {
        $sql = "SELECT
                    sum(cost) as totalSpend,
                    category
                FROM tb_purchase_purchase
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                GROUP BY category;";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets a user's average spend for each category
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(category) Array of categories and their average spend
     */
    public static function getAverageSpendingByCategory($userId) {
        $sql = "SELECT
                    costPerCatPerWeek.category,
                    avg(costPerCatPerWeek.cost) as averageSpend
                FROM (
                    SELECT
                        category,
                        cast(date_sub(datetime_purchased, interval dayofweek(datetime_purchased)-1 day) as date) as weekBeginning,
                        sum(cost) as cost
                    FROM tb_purchase_purchase
                    WHERE fk_pk_user_id = :userId
                    AND is_active = 1
                    GROUP BY weekBeginning, category
                ) as costPerCatPerWeek
                GROUP BY costPerCatPerWeek.category";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Gets date of user's first purchase (returns null if there have been none)
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return string date of first purchase
     *       | null
     */
    public static function getFirstPurchase($userId) {
        $sql = "SELECT
                    datetime_purchased as firstPurchase
                FROM tb_purchase_purchase
                WHERE fk_pk_user_id = :userId
                AND is_active = 1
                ORDER BY datetime_purchased ASC
                LIMIT 1";
        $params = array(
            ':userId' => $userId
        );
        $results = parent::fetchOne($sql, $params);
        if (isset($results->firstPurchase)) {
            $parts = explode(" ", $results->firstPurchase);
            return $parts[0];
        } else {
            return null;
        }
    }

    /**
     * Get user's spending (total, and so far) for each category for this week (returns empty string if there have
     * been no purchases)
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return array(category) Array of categories and their current spend per week and average spending
     *       | string Empty string
     */
    public static function getSpendByCategory($userId) {
        $firstPurchase = self::getFirstPurchase($userId);
        if ($firstPurchase === "") {
            return "";
        }
        $sql = "SELECT
                    totalSpendPerCat.category as category,
                    IFNULL(spendSoFarPerCat.cost, 0) as spendSoFar,
                    totalSpend,
                    totalSpend/FLOOR(DATEDIFF(now(),cast(date_sub(:firstPurchase, interval dayofweek(:firstPurchase)-1 day) as date))/7) as averageSpend
                FROM (
                    SELECT
                        sum(cost) as totalSpend,
                        tpc.name as category
                    FROM tb_purchase_purchase tp
                    JOIN tb_purchase_category tpc
                    ON tp.fk_pk_purchase_category_id = tpc.pk_purchase_category_id
                    WHERE tp.fk_pk_user_id = :userId
                    AND tp.is_active = 1
                    AND tpc.is_active = 1
                    GROUP BY fk_pk_purchase_category_id
                ) as totalSpendPerCat
                LEFT JOIN (
                    SELECT
                        tpc.name as category,
                        sum(cost) as cost
                    FROM tb_purchase_purchase tp
                    JOIN tb_purchase_category tpc
                    ON tp.fk_pk_purchase_category_id = tpc.pk_purchase_category_id
                    WHERE tp.fk_pk_user_id = :userId
                    AND tp.is_active = 1
                    AND tpc.is_active = 1
                    AND datetime_purchased >= cast(date_sub(now(), interval dayofweek(now())-1 day) as date)
                    AND datetime_purchased < cast(date_sub(date_add(now(),interval 7 day), interval dayofweek(date_add(now(),interval 7 day))-1 day) as date)
                    GROUP BY category
                ) as spendSoFarPerCat
                ON totalSpendPerCat.category = spendSoFarPerCat.category
                ORDER BY totalSpend DESC;";
        $params = array(
            ':userId'        => $userId,
            ':firstPurchase' => $firstPurchase
        );

        return parent::fetchAll($sql, $params);
    }

    /**
     * Get highest and lowest purchase cost, and first and last purchase date for a given user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get results for
     *
     * @return object Object containing max cost, min cost, min date and max date for user purchases
     */
    public static function getCostAndDateRanges($userId) {
        $sql = "SELECT
                    ceil(max(cost)) as maxCost,
                    floor(min(cost)) as minCost,
                    date_add(cast(max(datetime_purchased) as date), interval 1 day) as maxDate,
                    cast(min(datetime_purchased) as date) as minDate
                FROM tb_purchase_purchase
                WHERE fk_pk_user_id = :userId
                AND is_active = 1";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Updates a specific purchase with new information
     *
     * @author Craig Knott
     *
     * @param int    $userId     Id of user whose purchase it is
     * @param int    $purchaseId Id of the purchase to edit
     * @param string $items      New name for the purchase
     * @param float  $cost       New cost for the puchase
     * @param string $date       New date for the purchase (yyyy/mm/dd)
     * @param int    $category   New category id for the purchase
     *
     * @return void
     */
    public static function updatePurchase($userId, $purchaseId, $items, $cost, $date, $category) {
        $sql = "UPDATE tb_purchase_purchase
                SET items = :items,
                    cost = :cost,
                    datetime_purchased = :date,
                    fk_pk_purchase_category_id = (
                        SELECT pk_purchase_category_id
                        FROM tb_purchase_category
                        WHERE name = :category
                    ),
                    updated_by = :userId,
                    datetime_updated = NOW()
                WHERE pk_purchase_purchase_id = :purchaseId
                  AND fk_pk_user_id = :userId;";
        $params = array(
            ':userId'     => $userId,
            ':purchaseId' => $purchaseId,
            ':items'      => $items,
            ':cost'       => $cost,
            ':date'       => $date,
            ':category'   => $category
        );
        parent::execute($sql, $params);
    }

    /**
     * Adds a new custom category for the user
     *
     * @author Craig Knott
     *
     * @param string $name   Name of the new category
     * @param int    $userId Id of user to add category to
     *
     * @return void
     */
    public static function addNewCategory($name, $userId) {
        $sql = "INSERT INTO tb_purchase_category (
                    name,
                    fk_pk_user_id,
                    is_active,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :name,
                    :userId,
                    1,
                    :userId,
                    NOW(),
                    :userId,
                    NOW()
                );";
        $params = array(
            ':name'   => $name,
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Delete or restore a user's custom category
     *
     * @author Craig Knott
     *
     * @param int $categoryId Id of the category to update
     * @param int $active     Status of the category (0 or 1)
     * @param int $updatedBy  Who updated this row
     *
     * @return void
     */
    public static function updateActiveOfCustomCategory($categoryId, $active, $updatedBy) {
        $sql = "UPDATE tb_purchase_category
                SET is_active = :active,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_purchase_category_id = :categoryId;";
        $params = array(
            ':categoryId' => $categoryId,
            ':active'     => $active,
            ':updatedBy'  => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Update the name of a custom category
     *
     * @author Craig Knott
     *
     * @param int $categoryId Id of the category to update
     * @param int $name       New name of the category
     * @param int $updatedBy  Who updated this row
     *
     * @return void
     */
    public static function updateCustomCategoryName($categoryId, $name, $updatedBy) {
        $sql = "UPDATE tb_purchase_category
                SET name = :name,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_purchase_category_id = :categoryId;";
        $params = array(
            ':categoryId' => $categoryId,
            ':name'       => $name,
            ':updatedBy'  => $updatedBy
        );
        parent::execute($sql, $params);
    }

    public static function getAllLoansForUser($userId) {
        $sql = "select
                    pl.pk_purchase_loan_id as id,
                    pl.loaner as loaner_id,
                    pl.loaned_to as loanee_id,
                    pl.total_amount,
                    pl.datetime_updated as last_updated,
                    pl.updated_by,
                    pls.loaner_title,
                    pls.loanee_title,
                    pls.receiver_title,
                    pls.pk_purchase_loanstep_id as step_id,
                    pl.created_by as owner,
                    CASE
                        WHEN u.pk_user_id = pl.created_by THEN u.fname
                        ELSE u2.fname
                    END as owner_name,
                    CASE
                        WHEN u.pk_user_id = :userId THEN 'You'
                        ELSE CONCAT(u.fname, ' ', u.lname)
                    END as loaner,
                    CASE
                        WHEN u2.pk_user_id = :userId THEN 'You'
                        ELSE CONCAT(u2.fname, ' ', u2.lname)
                    END as loanee,
                    (
                        select
                            CASE
                                WHEN sum(amount) IS NULL THEN pl.total_amount
                                ELSE pl.total_amount - sum(amount)
                            END AS amount_remaining
                        from tb_purchase_loan_payment
                        where fk_pk_purchase_loan_id = pl.pk_purchase_loan_id
                        and is_accepted = 1
                        and is_rejected = 0
                    ) as amount_remaining
                from tb_purchase_loan pl
                join tb_user u
                on u.pk_user_id = pl.loaner
                join tb_user u2
                on u2.pk_user_id = pl.loaned_to
                join tb_purchase_loanstep pls
                on pl.fk_pk_purchase_loanstep_id = pls.pk_purchase_loanstep_id
                where pl.loaner = :userId
                or pl.loaned_to = :userId;";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Based on username, find whether whether a user has access to the purchases app
     *
     * @param string $username Username to search for
     *
     * @return user || false Either a user object, or false (meaning they have this app suppressed)
     */
    public static function findPotentialLoaner($username) {
        $sql = "SELECT
                    lname,
                    fname,
                    username,
                    pk_user_id as id,
                    (
                        CASE WHEN EXISTS(
                            select pk_user_app_suppression_id
                            from tb_user_app_suppression uas
                            join tb_user u
                            on u.pk_user_id = uas.fk_pk_user_id
                            where username = :username
                            and uas.is_active = 1
                            and fk_pk_app_id = 1
                        )
                        THEN 0
                        ELSE 1
                        END
                    ) as can_loan
                FROM tb_user
                WHERE username = :username
                AND is_active = 1;";
        $params = array(
            'username' => $username
        );
        $result = parent::fetchOne($sql, $params);

        if (property_exists($result, 'scalar')) {
            return false;
        }
        return $result;
    }

    /**
     * Create a new loan
     *
     * @param int   $loaner Id of the loaner
     * @param int   $loanee Id of the loanee
     * @param float $amount Amount that was loaned
     * @param int   $userId Which of the two created the loan proposal
     *
     * @return void
     */
    public static function addNewLoan($loaner, $loanee, $amount, $userId) {
        $sql = "INSERT INTO tb_purchase_loan (
                    loaner,
                    loaned_to,
                    total_amount,
                    fk_pk_purchase_loanstep_id,
                    datetime_created,
                    datetime_updated,
                    created_by,
                    updated_by,
                    is_active
                ) values (
                    :loaner,
                    :loanee,
                    :amount,
                    1,
                    NOW(),
                    NOW(),
                    :userId,
                    :userId,
                    1
                )";
        $params = array(
            'loaner' => $loaner,
            'loanee' => $loanee,
            'amount' => $amount,
            'userId' => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Get the steps involved in a loan
     *
     * @return array(string) Indexed array of steps
     */
    public static function getLoanSteps() {
        $sql = "SELECT
                    pk_purchase_loanstep_id as id,
                    pk_purchase_loanstep_id as step,
                    loaner_title,
                    loanee_title,
                    receiver_title
                FROM tb_purchase_loanstep";
        return parent::fetchAll($sql);
    }

    /**
     * Update a particular loan to a given step
     *
     * @author Craig Knott
     *
     * @param int $loanId    Id of the loan to update
     * @param int $stepId    The step to update to loan to
     * @param int $updatedBy The id of the user who updated the step
     *
     * @return void
     */
    public static function updateLoanStep($loanId, $stepId, $updatedBy) {
        $sql = "UPDATE tb_purchase_loan
                SET fk_pk_purchase_loanstep_id = :stepId,
                    datetime_updated = NOW(),
                    updated_by = :updatedBy
                WHERE pk_purchase_loan_id = :loanId";
        $params = array(
            'loanId'    => $loanId,
            'stepId'    => $stepId,
            'updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Add a new payment to a loan
     *
     * @param int   $loanId    Id of the loan to update
     * @param float $amount    Amount being repaid
     * @param int   $updatedBy Id of user making the payment
     * @param int   $createdBy Who created this row
     *
     * @return void
     */
    public static function makeLoanPayment($loanId, $amount, $updatedBy, $createdBy) {
        $sql = "INSERT INTO tb_purchase_loan_payment (
                    fk_pk_purchase_loan_id,
                    is_accepted,
                    is_active,
                    amount,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :loanId,
                    0,
                    1,
                    :amount,
                    :createdBy,
                    NOW(),
                    :createdBy,
                    NOW()
                )";
        $params = array(
            'loanId'    => $loanId,
            'amount'    => $amount,
            'createdBy' => $createdBy
        );
        parent::execute($sql, $params);

        $sql = "UPDATE tb_purchase_loan
                SET datetime_updated = NOW(),
                    updated_by = :updatedBy
                WHERE pk_purchase_loan_id = :loanId";
        $params = array(
            'loanId'    => $loanId,
            'updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }
}