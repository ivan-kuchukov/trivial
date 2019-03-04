# Trivial
php-framework with trivial functionality.


### App
Work with App parameters:
````
App::setParam('new-param-name','new-param-value'); // set new-param-name=new-param-value
var_dump(App::params('new-param-name')); // get new-param-name
````
Create database:
````
App::createDb('db'); // create object of new database from configuration parameter "db"
````
Get data for parameter $param from GET and POST requests:
````
var_dump(App::get($param));
var_dump(App::post($param));
````
Operations with UID Application:
````
if(!empty(App::post('uid'))) {
    App::setUID(App::post('uid')); // Set UID from post-request
}
echo App::getUID(); // Get UID
````


### Database
For work with Database use App::db() and parameter "db" in configuration file.
Or create database connect by manual:
````
try {
    $db = DatabaseFactory::create([
        "errorLog"=>"display", // error actions: display, log, ignore 
        "queriesLog"=>false, // log ALL queries
        "type"=>"MariaDB", // Your database type (MySQL,MariaDB,PostgreSQL)
        "driver"=>"PDO", // Database driver (original,PDO)
        "servername"=>"localhost", // Your database address
        "username"=>"test", // Your database username
        "password"=>"test", // Your database password
        "database"=>"test", // Your database name
        "persistentConnection"=>true,
        "attributes"=>[
            Database::ATTR_ERRMODE=>Database::ERRMODE_EXCEPTION,
            Database::ATTR_DEFAULT_FETCH_MODE=> Database::FETCH_ASSOC,
        ],
    ]);
} catch (\Exception $e) {
    echo 'ERROR: ['. $e->getCode() . '] ' . $e->getMessage() . PHP_EOL;
    return false;
}
````

Database ddl operations (you must set ATTR_ERRMODE=ERRMODE_EXCEPTION for catch errors in sql):
````
try {
    $query = $db->exec("CREATE TABLE test ("
        . "id int(6) NOT NULL AUTO_INCREMENT, "
        . "number int(6), "
        . "string varchar(100), "
        . "PRIMARY KEY (id) )");
} catch (\Exception $e) {
    echo 'ERROR: ['. $e->getCode() . '] ' . $e->getMessage() . PHP_EOL;
    return false;
}
````
Database. Execute query with binding (using universal binding, 
but you can use specific for your database style):
````
$query="SELECT * FROM table WHERE id=:num OR string=:num2)";
$result = $db->exec($query,['num'=>[1,'i'],'num2'=>'text']);
var_dump($result->getAll());
// or var_dump($result->getArray());
// or var_dump($result->getScalar());
````
Database transaction:
````
$db->transaction();
$db->exec("INSERT INTO table (number) VALUES (num)",['num'=>[3,'i']]); 
$db->commit(); 
// or $db->rollback();
````
Database. Specific for database methods:
Only for MySQL/MariaDB you can use method "getInsertId":
````
// MySQL binding style, but better use universal binding style
$db->exec("INSERT INTO table (number) VALUES (?)",[[3,'i']]); 
echo "id for new row is " . $db->getInsertId();
````
In PostgreSQL you can use another way:
````
// PostgreSQL binding style, but better use universal binding style
$result = $db->exec("INSERT INTO table (number) VALUES ($1) RETURNING id",[[3,'i']]);
echo "id for new row is " . $result->getScalar();
````


### Translator
Create file with translations - translate/ru/t.php (ru - language):
````
<?php
return [
    'main'=>[
        'begin'=>'начало',
        'end'=>'конец',
    ],
];
````
And use next commands in Your template:
````
Translator::setLanguage('ru');
echo Translator::translate('begin');
echo Translator::translate('end');
````
Or use alias:
````
echo t('begin');
````


### HTML Helper. Pagination
Example of pagination:
````
<?php 
$pagination = [
    'start'=>1, // start element
    'size'=>10, // count of elements on page
    'count'=>50 // count of all elements
];
?>
<nav aria-label="Page navigation">
  <ul class="pagination">
    <?php foreach (HtmlHelper::getPagination($pagination) as $key=>$value) : ?>
    <li class="page-item<?= ($value!=$pagination['start']) ?: ' active' ?>">
        <a class="page-link" href="<?= $_SERVER['BASE'] ?>/page?start=<?= $value ?>">
            <?= $key ?></a></li>
    <?php endforeach ?>
  </ul>
</nav>
````


### Array Helper. Type of array
Example:
````
ArrayHelper::getType($array);
````
function return words: 'associative','sequential','mixed' or null;


