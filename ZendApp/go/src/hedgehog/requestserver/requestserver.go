package RequestServer

import (
    "net/http"
    "encoding/json"
)

type Response struct {
    Message string
    Status int64
    Data []map[string]interface {}
}

func Error(msg string, w http.ResponseWriter) {
    json, err := json.Marshal(Response{msg, 1, nil})
    if err == nil {
        w.Write(json)    
    }
}

func Serve(data []byte, w http.ResponseWriter) {
    w.Header().Set("Content-Type", "application/json")
    w.Write(data)
}