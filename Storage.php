<?php
/**
 * Extends Storage\Pdo to interface with your database to get user and learner information
 * User: solomonliu
 * Date: 2017-03-20
 * Time: 22:58
 */

class Storage extends OAuth2\Storage\Pdo
{
    const USER_KEYS = array('sub', 'name');
    const LEARNER_KEYS = array('sub', 'name', 'gender', 'birthdate');
    /**
     * Storage constructor. Initializes the database connection.
     */
    public function __construct()
    {
        $connection = array(
            'dsn' => 'sqlite:demo_database.sqlite', //Replace with your database connection string
            'username' => null, //Replace with the username to connect to your database
            'password' => null, //Replace with the password to connect to your database
            'options' => array()
        );
        parent::__construct($connection);
    }

    /**
     * This method should return true if the username/password combination is valid, false if the username does not exist
     * or if the username/password combination is invalid.
     *
     * @param $username string
     * @param $password string
     * @return bool true if username/password combination is valid, false if not, or username does not exist
     */
    public function checkUserCredentials($username, $password)
    {
        $stmt = $this->db->prepare("select password from user_info where username = :username");
        $stmt->execute(array('username'=>$username));
        if ($hash = $stmt->fetchColumn()) {
            return password_verify($password, $hash);
        }
        return false;
    }

    /**
     * This function should return an associative array with keys 'sub', 'name', 'preferences', and 'learners'
     *
     * @param $userId string userId to get user info for
     * @return array|bool associative array with user properties
     */
    public function getUser($userId)
    {
        $stmt = $this->db->prepare("select id sub, name, user_prop_1, user_prop_2 from user_info where id = :userId");

        $stmt->execute(array('userId'=>$userId));
        if ($userInfo = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //all properties not included in USER_KEYS are put in preferences
            $userInfo['preferences'] = array_diff_key($userInfo, array_intersect_key($userInfo, array_flip(Storage::USER_KEYS)));
            //remove everything bu USER_KEYS and preferences
            $userInfo = array_intersect_key($userInfo, array_flip(array_merge(Storage::USER_KEYS, array('preferences'))));
            $userInfo['learners'] = $this->getLearnerInfo($userId);
            return $userInfo;
        }
        return false;
    }

    /**
     * @param $username string username to look up
     * @return string|false userId userId of associated with the
     */
    public function getUserId($username) {
        $stmt = $this->db->prepare("select id from user_info where username = :username");
        $stmt->execute(array('username'=>$username));
        return $stmt->fetchColumn();
    }

    /**
     * This function should return an array of associative arrays with keys 'sub', 'name', 'gender', 'birthdate', 'preferences'
     *
     * @param $userId string userId to get learners for
     * @return array array of associative arrays representing learners
     */
    private function getLearnerInfo($userId) {
        $stmt = $this->db->prepare("select id sub, name, gender, birthdate, learner_prop_1, learner_prop_2 from learner_info where user_id = :userId");
        $stmt->execute(array('userId'=>$userId));
        $learners = array();
        if ($learnerList = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($learnerList as $learnerInfo) {
                //all properties not included in LEARNER_KEYS are put in preferences
                $learnerInfo['preferences'] = array_diff_key($learnerInfo, array_intersect_key($learnerInfo, array_flip(Storage::LEARNER_KEYS)));
                //remove everything but LEARNER_KEYS and preferences
                $learnerInfo = array_intersect_key($learnerInfo, array_flip(array_merge(Storage::LEARNER_KEYS, array('preferences'))));
                array_push($learners, $learnerInfo);
            }
        }
        return $learners;
    }

    /**
     * Creates the necessary tables in the database
     */
    public function initDb() {
        $this->db->exec($this->getBuildSql());
    }

    /**
     * Set the default private key to use for JWT signing
     *
     * @param $key string default private key to use
     */
    public function setPrivateKey($key) {
        if ($this->getPrivateKey()) {
            $statement = $this->db->prepare(sprintf('UPDATE %s SET private_key = :key WHERE client_id IS NULL', $this->config['public_key_table']));
        } else {
            $statement = $this->db->prepare(sprintf('INSERT INTO %s (client_id, private_key) VALUES (NULL, :key)', $this->config['public_key_table']));
        }
        $statement->execute(array('key'=>$key));
    }
}