<?php

namespace AsyncMysql;

use AsyncMysql\EventLoop;
use AsyncMysql\Client;

use Evenement\EventEmitter;

class Query extends EventEmitter
{
    private $loop;

    private $client;

    private $connection;

    private $query;

    public function __construct(EventLoop $loop, Client $client, $query)
    {
        $this->loop   = $loop;
        $this->client = $client;
        $this->query  = $query;

        $loop->addQuery($this);
    }

    public function getConnection()
    {
        $this->connect();

        return $this->connection;
    }

    public function connect()
    {
        if (is_null($this->connection)) {
            $this->connection = $this->client->getConnection();
        }
    }

    public function isExecuted()
    {
        return isset($this->connection);
    }

    public function execute()
    {
        $this->connect();

        mysqli_query($this->connection, $this->query, MYSQLI_ASYNC);
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getAsyncResult()
    {
        return mysqli_reap_async_query($this->connection);
    }

    public function handleAsyncResult()
    {
        $result = $this->getAsyncResult();

        if ($result) {
            $this->emit('result', array($result, $this));
        } else {
            $this->emit('error', array($this));
        }
    }
}
