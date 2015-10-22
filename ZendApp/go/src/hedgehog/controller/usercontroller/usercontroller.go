package UserController

import (
    "net/http"
    "strconv"
    "hedgehog/factory/userfactory"
    "hedgehog/requestserver"
)

func GetUserHandler(w http.ResponseWriter, r *http.Request) {

    params := r.URL.Query()

    userId, err := strconv.Atoi(params.Get(":id"))
    if err != nil {
        RequestServer.Error("Non-numeric user id provided", w)
        return
    }

    res := User_Factory.GetUser(userId)
    RequestServer.Serve(res, w)
}

func GetUserByUsernameHandler(w http.ResponseWriter, r *http.Request) {
	params := r.URL.Query()

	username := params.Get(":username")
	res := User_Factory.GetUserByUsername(username)

	RequestServer.Serve(res, w)
}

func GetAllUsersHandler(w http.ResponseWriter, r *http.Request) {
    res := User_Factory.GetUsers()
    RequestServer.Serve(res, w)
}

func UpdateUserHandler(w http.ResponseWriter, r *http.Request) {
    params := r.URL.Query()

    userId, err := strconv.Atoi(params.Get(":id"))
    if err != nil {
        RequestServer.Error("Non-numeric user id provided", w)
        return
    }

    fname := params.Get(":fname")
    lname := params.Get(":lname")

    res := User_Factory.UpdateUser(userId, fname, lname)

    RequestServer.Serve(res, w)
}

func CreateUserHandler(w http.ResponseWriter, r *http.Request) {
    params := r.URL.Query()

    username := params.Get(":username")

    // Check username is unique
    unique := User_Factory.GetUserByUsername(username)

	RequestServer.Serve(unique, w)


/*
    fname := params.Get(":fname")
    lname := params.Get(":lname")
    email := params.Get(":email")
    created_by := params.Get(":creator")
    updated_by := params.Get(":creator")

    res := User_Factory.CreateUser(username, fname, lname, email, created_by, updated_by)

    RequestServer.Serve(res, w)*/
}
