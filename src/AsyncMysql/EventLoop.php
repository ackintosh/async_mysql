<?php

namespace AsyncMysql;

use AsyncMysql\Client;
use AsyncMysql\Query;
use AsyncMysql\QueryPoller;

class EventLoop
{
    private $poller;
    private $finishedQueries;
    private $failedQueries;
    public $onRollback;

    public function __construct()
    {
        $this->poller = new QueryPoller(1);
        $this->finishedQueries  = array();
        $this->failedQueries    = array();
    }

    public function connect($host = null, $user = null, $password = null, $dbname = null, $port = null, $socket = null)
    {
        return new Client($this, $host, $user, $password, $dbname, $port, $socket);
    }

    public function addQuery(Query $query)
    {
        $this->poller->addQuery($query);
    }

    public function removeQuery(Query $query)
    {
        $this->poller->removeQuery($query);
    }

    public function run()
    {
        while ($this->poller->isUnfinished()) {
            if ($this->poller->poll()) {
                $queries = $this->poller->getFinishedQueries();

                foreach ($queries as $query) {
                    $result = $query->handleAsyncResult();

                    $this->finishedQueries[] = $query;
                    if (!$result) $this->failedQueries[] = $query;
                }
            }
        }

        if ($this->onRollback) $this->execute();
    }

    public function execute()
    {
        if (empty($this->failedQueries))
            $this->commit();
        else
            $this->rollback();
    }

    public function commit()
    {
        foreach ($this->finishedQueries as $query)
        {
            mysqli_commit($query->getConnection());
        }
    }

    public function rollback()
    {
        foreach ($this->finishedQueries as $query)
        {
            mysqli_rollback($query->getConnection());
        }

        call_user_func_array($this->onRollback, array($this->failedQueries));
    }

    public function usingTransaction(\Closure $onRollback)
    {
        $this->onRollback = $onRollback;
    }
}
