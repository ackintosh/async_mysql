<?php
require __DIR__ . '/../vendor/autoload.php';

$loop = new AsyncMysql\EventLoop;
$loop->usingTransaction(function ($failedQueries) {
    echo 'rollback:' . PHP_EOL;
    foreach ($failedQueries as $query) {
        echo $query->getQuery(), PHP_EOL;
    }
});

$conn = $loop->connect('localhost');

foreach (array(3, 6, 9, 1, 5, 2) as $i => $sleep) {
    $sql = "insert into todo (todo) values ('{$i}')";

    // To roll back intentionally.
    if ($i === 1 || $i === 3) $sql = "invalid sql {$i}";
    $query = $conn->query($sql);

    $query->on('result', function ($result, $query) use ($i) {
        echo "{$i}: ", $query->getQuery(), PHP_EOL;
        echo PHP_EOL;
    });

    $query->on('error', function ($query) {
        echo "Error: ", $query->getQuery(), PHP_EOL;
        echo PHP_EOL;
    });
}

$loop->run();

