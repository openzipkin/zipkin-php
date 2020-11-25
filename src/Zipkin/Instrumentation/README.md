# Zipkin instrumentation for Mysqli

```php
use Zipkin\Instrumentation\Mysqli\Mysqli;

$mysqli = new Mysqli($tracer, [], "127.0.0.1", "my_user", "my_password", "sakila");

if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}

$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_ONLY);

$mysqli->query("SELECT first_name, last_name FROM actor");
$mysqli->commit();

$mysqli->close();
```
