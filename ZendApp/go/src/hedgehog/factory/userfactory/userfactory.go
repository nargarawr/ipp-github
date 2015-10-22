package User_Factory

import (
    _ "github.com/go-sql-driver/mysql"
    "log"
    "hedgehog/factory/modelfactory"
)

var db = Model_Factory.GetDb()

// Get a specific user
func GetUser(userId int) []byte {
    qry := `select
                pk_user_id,
                username,
                email,
                fname,
                lname,
                login_count
            from tb_user
            where pk_user_id = ?`
    rows, err := db.Query(qry, userId)
    if err != nil {
        log.Print(err)
    }
    defer rows.Close()

    return Model_Factory.GetResultSet(rows)
}

// Get user by username
func GetUserByUsername (username string) []byte {
	qry := `select
				pk_user_id,
				username,
				email,
				fname,
				lname,
				login_count
			from tb_user
			where username = ?`
	rows, err := db.Query(qry, username)
	if err != nil {
		log.Print(err)
	}

	defer rows.Close()

	return Model_Factory.GetResultSet(rows)
}

// Get all users
func GetUsers() []byte {
    qry := `select
                pk_user_id,
                username,
                email,
                fname,
                lname,
                login_count
            from tb_user`
    rows, err := Model_Factory.GetDb().Query(qry)
    if err != nil {
        log.Print(err)
    }
    defer rows.Close()

    return Model_Factory.GetResultSet(rows)
}

func UpdateUser(userId int, fname string, lname string) []byte {
    qry := `update tb_user
            set fname = ?,
                lname = ?
            where pk_user_id = ?`
    update, err := db.Prepare(qry)
    if err != nil {
        log.Print(err)
    }

    update.Exec(fname, lname, userId)

    // Return the updated user
    return GetUser(userId)
}

func CreateUser(username string, fname string, lname string, email string, created_by string, updated_by string) []byte {
    qry := `insert into tb_user (
                username,
                is_active,
                user_type,
                password,
                datetime_created,
                email,
                fname,
                lname,
                login_count,
                datetime_updated,
                created_by,
                updated_by
            ) values (
                ?,
                1,
                1,
                MD5('password'),
                NOW(),
                ?,
                ?,
                ?,
                0,
                NOW(),
                ?,
                ?
            )`
    created, err := db.Prepare(qry)
    if err != nil {
        log.Print(err)
    }

    created.Exec(username, fname, lname, email, created_by, updated_by)



    // Return the new user
    return GetUser(1)


}
