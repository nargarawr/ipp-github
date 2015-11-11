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
                    report_message
                ) VALUES (
                    :reporter_id,
                    :type,
                    :reported_item_id,
                    :report_message
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
}