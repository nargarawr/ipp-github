<?

class UserFactory extends ModelFactory {

    public static function updatePassword() {

    }

    public static function updateUserDetails($fname, $lname, $email, $location) {
        $sql = "";
        $params = array(
            ':fname' => $fname,
            ':lname' => $lname,
            ':email' => $email,
            ':location' => $location
        );
        parent::execute($sql, $params);
    }

}