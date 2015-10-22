<?php

class ApiFactory extends ModelFactory {

    private $userId;

    function __construct($userId) {
        $this->userId = $userId;
    }

    public function call($urlStub) {
        $curl = curl_init();
        $url = self::getUrl($urlStub);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $url
        ));
        $resp = curl_exec($curl);

        logger('Accessed ' . $url, 'LOW', $this->userId);
        curl_close($curl);

        if (false) {
            return (object) array(
                'Message' => 'The page requested does not exist',
                'Status'  => 1,
                'Data'    => null
            );
        }

        $json = Zend_Json::decode($resp);
        return (object) $json;
    }

    private function getUrl($stub) {
        $url = 'http://craigknott.com:8000/v1/api/' . $stub . '/';
        return $url;
    }
}