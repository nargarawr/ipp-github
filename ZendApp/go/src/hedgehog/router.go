package main

import (
	"github.com/bmizerany/pat"
	_ "github.com/go-sql-driver/mysql"
	"hedgehog/controller/usercontroller"
	"net/http"
)

func Routes() *pat.PatternServeMux {
	mux := pat.New()

        // List of routes and handlers
        mux.Get("/api/v1/user/get/:id/", http.HandlerFunc(UserController.GetUserHandler))
        mux.Get("/api/v1/user/getall/", http.HandlerFunc(UserController.GetAllUsersHandler))
        mux.Get("/api/v1/user/update/:id/:fname/:lname/", http.HandlerFunc(UserController.UpdateUserHandler))
        mux.Get("/api/v1/user/create/:username/:fname/:lname/:email/:creator/", http.HandlerFunc(UserController.CreateUserHandler))
		mux.Get("/api/v1/user/getbyusername/:username/", http.HandlerFunc(UserController.GetUserByUsernameHandler))

	return mux
}
