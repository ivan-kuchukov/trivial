<?php


namespace trivial\models;


trait DatabaseLoggerTrait
{
    public $logger;

    public function exec(string $query, array $vars=[]) {
        $result = parent::exec($query, $vars);
        $this->logger->logQuery('exec',$vars,$result);
        return $result;
    }

    public function getAll($syncId=null) {
        $result = parent::getAll($syncId);
        $this->logger->logResult('getAll',null,$result);
        return $result;
    }

    public function getArray() {
        $result = parent::getArray();
        $this->logger->logResult('getArray',null,$result);
        return $result;
    }

    public function getScalar() {
        $result = parent::getScalar();
        $this->logger->logResult('getScalar',null,$result);
        return $result;
    }

    public function statistics($arguments=null) {
        $this->logger->statistics($arguments);
    }
}