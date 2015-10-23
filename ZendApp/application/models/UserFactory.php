<?

class UserFactory extends ModelFactory {
       
    public static function testSQL() {
        $sql = "SELECT * FROM tb_user";
        $result = parent::fetchAll($sql);
        return $result;
    }

}