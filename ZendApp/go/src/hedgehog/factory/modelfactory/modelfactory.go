package Model_Factory

import (
    "database/sql"
    "encoding/json"
    _ "github.com/go-sql-driver/mysql"
    "log"
    "hedgehog/requestserver"
)

// Format a result set as a response object
func GetResultSet(rows *sql.Rows) []byte {
    columns, err := rows.Columns()
    if err != nil {
        log.Fatal(err)
    }
    count := len(columns)
    tableData := make([]map[string]interface{}, 0)
    values := make([]interface{}, count)
    valuePtrs := make([]interface{}, count)
    for rows.Next() {
        for i := 0; i < count; i++ {
            valuePtrs[i] = &values[i]
        }
        rows.Scan(valuePtrs...)
        entry := make(map[string]interface{})
        for i, col := range columns {
            var v interface{}
            val := values[i]
            b, ok := val.([]byte)
            if ok {
                v = string(b)
            } else {
                v = val
            }
            entry[col] = v
        }
        tableData = append(tableData, entry)
    }

    jsonData, err := json.Marshal(RequestServer.Response{"Success", 0, tableData})
    if err != nil {
        log.Fatal(err)
    }

    return jsonData
}

// Get the database to query
func GetDb() *sql.DB {
    db, err := sql.Open("mysql", "root:xD1NCzMlZv@tcp(178.62.46.20:3306)/cxk")
    if err != nil {
        log.Fatal(err)
    }

    return db
}