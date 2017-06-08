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
        if (!$userId) {
            return false;
        }
        $stmt = $this->db->prepare("select full_name name, user_prop_1, user_prop_2 from user_info where id = :userId");

        $stmt->execute(array('userId'=>$userId));
        if ($userInfo = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //if sub is returned, make sure it matches userId, populate with userId if not
            if (!array_key_exists('sub', $userInfo)) {
                $userInfo['sub'] = $userId;
            } else if ($userInfo['sub'] != $userId) {
                return false;
            }
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
        $stmt = $this->db->prepare("select id sub, learner_name name, learner_gender gender, birthday birthdate, learner_prop_1, learner_prop_2 from learner_info where user_id = :userId");
        $stmt->execute(array('userId'=>$userId));
        $learners = array();
        if ($learnerList = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($learnerList as $learnerInfo) {
                //skip this learner if it does not have 'sub'
                if (!array_key_exists('sub', $learnerInfo) || !$learnerInfo['sub']) {
                    continue;
                }
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

    //Override these methods because 'order by client_id is not null' doesn't work for all SQL dialects
    public function getPublicKey($client_id = null)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT public_key FROM %s WHERE client_id=:client_id OR client_id IS NULL', $this->config['public_key_table']));
        $stmt->execute(compact('client_id'));
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $result['public_key'];
        }
    }

    public function getPrivateKey($client_id = null)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT private_key FROM %s WHERE client_id=:client_id OR client_id IS NULL', $this->config['public_key_table']));
        $stmt->execute(compact('client_id'));
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $result['private_key'];
        }
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT encryption_algorithm FROM %s WHERE client_id=:client_id OR client_id IS NULL', $this->config['public_key_table']));
        $stmt->execute(compact('client_id'));
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $result['encryption_algorithm'];
        }
        return 'RS256';
    }
}