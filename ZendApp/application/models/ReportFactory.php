<?

/**
 * Class ReportFactory
 *
 * Manages all reports, and their interaction with the database
 *
 * @author Craig Knott
 *
 */
class ReportFactory extends ModelFactory {

    /**
     * Adds a new report
     *
     * @author Craig Knott
     *
     * @param int    $reporterId     The id of the user reporting this content
     * @param string $type           The type of report (comment or route)
     * @param int    $reportedItemId The Id of the reported comment or route
     * @param string $reportMessage  Message provided by the user explaining why they reported this
     *
     * @return int The Id of the report
     */
    public static function addReport($reporterId, $type, $reportedItemId, $reportMessage) {
        $sql = "INSERT INTO tb_report (
                    reporter_id,
                    type,
                    reported_item_id,
                    report_message,
                    datetime,
                    is_resolved
                ) VALUES (
                    :reporter_id,
                    :type,
                    :reported_item_id,
                    :report_message,
                    NOW(),
                    0
                )";
        $params = array(
            ':reporter_id'      => $reporterId,
            ':type'             => $type,
            ':reported_item_id' => $reportedItemId,
            ':report_message'   => $reportMessage
        );

        $reportId = parent::execute($sql, $params, true);
        return $reportId;
    }

    /**
     * Gets all non-resolved reports, including a link to the route if it's a reported route, and the content of the
     * comment if it's a reported comment
     *
     * @author Craig Knotts
     *
     * @return array Of reports
     */
    public static function getAll() {
        $sql = "SELECT
                    pk_report_id AS id,
                    type,
                    reported_item_id,
                    report_message,
                    IF (type = 'comment',
                        (SELECT comment FROM tb_comment WHERE pk_comment_id = reported_item_id),
                        NULL
                    ) AS reported_comment,
                    IF (type = 'route',
                        CONCAT('/route/detail/id/',reported_item_id),
                        NULL
                    ) AS reported_route,
                    datetime,
                    username
                FROM tb_report
                JOIN tb_user
                ON tb_user.pk_user_id = tb_report.reporter_id
                WHERE is_resolved = 0
                ORDER BY datetime ASC";
        $params = array();
        return parent::fetchAll($sql, $params);
    }

    /**
     * Given a report, marks it as resolved, with the reason for the resolution, and who resolved it. As well as
     * marking all other reports for the same thing as resolved
     *
     * @author Craig Knott
     *
     * @param int    $reportId       The id of the report being resolved
     * @param string $resolution     What the resolution was
     * @param int    $resolvedBy     Who resolved this issue
     * @param string $type           Route or comment
     * @param int    $reportedItemId The id of the item that was reported
     */
    public static function resolveReport($reportId, $resolution, $resolvedBy, $type, $reportedItemId) {
        $sql = "UPDATE tb_report
                SET is_resolved = 1,
                    resolution = :resolution,
                    resolved_by = :resolvedBy
                WHERE pk_report_id = :reportId";
        $params = array(
            ':resolution' => $resolution,
            ':resolvedBy' => $resolvedBy,
            ':reportId'   => $reportId
        );

        parent::execute($sql, $params);

        $relatedReports = ReportFactory::getReportsForId($type, $reportedItemId);
        if (count($relatedReports) > 0) {
            ReportFactory::resolveRelatedReports($relatedReports, $resolution, $resolvedBy);
        }
    }

    /**
     * Gets all reports for the specified item
     *
     * @author Craig Knott
     *
     * @param string $type           Comment or route
     * @param int    $reportedItemId The id of the reported route or comment
     *
     * @return Array of all reports for this thing
     */
    public static function getReportsForId($type, $reportedItemId) {
        $sql = "SELECT
                    pk_report_id as id
                FROM tb_report
                WHERE type = :type
                AND reported_item_id = :reportedItemId
                AND is_resolved = 0";
        $params = array(
            ':type'           => $type,
            ':reportedItemId' => $reportedItemId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Resolves all reports in the {$reports} array
     *
     * @author Craig Knott
     *
     * @param array(int) $reports    Array of ids of reports to resolve
     * @param string     $resolution What the resolution was
     * @param int        $resolvedBy Who resolved the initial report
     */
    public static function resolveRelatedReports($reports, $resolution, $resolvedBy) {
        $reportIds = "";
        foreach ($reports as $report) {
            $reportIds .= $report->id . ',';
        }
        $reportIds = rtrim($reportIds, ',');

        $sql = "UPDATE tb_report
                SET is_resolved = 1,
                    resolution = :resolution,
                    resolved_by = :resolvedBy
                WHERE pk_report_id IN (" . $reportIds . ")";
        $params = array(
            ':resolution' => $resolution . " by proxy",
            ':resolvedBy' => $resolvedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Gets how many unresolved reports there are in the database
     *
     * @author Craig Knott
     *
     * @return int The number of unresolved reports
     */
    public static function getUnresolvedReportCount() {
        $sql = "SELECT
                    count(pk_report_id) as num
                FROM tb_report
                WHERE is_resolved = 0";
        $params = array();
        return parent::fetchOne($sql, $params)->num;
    }
}