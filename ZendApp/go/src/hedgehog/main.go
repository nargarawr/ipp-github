package main

import (
	"log"
	"net/http"
)

func main() {
	// Compile list of routes
	r := Routes()
	http.Handle("/", r)

	// Launch server
	log.Println("Listening...")
	http.ListenAndServe("craigknott.com:8000", nil)
}