# Trivial - php-framework

###############################################################################
#                               WORK WITH DATABASE                            #
###############################################################################

Database connect:
$db = new MariaDatabase([
        "errorLog"=>"display", // error actions: display, log, ignore 
        "queriesLog"=>false, // log ALL queries
        "type"=>"MariaDB", // Your database type
        "servername"=>"localhost", // Your database address
        "username"=>"test", // Your database username
        "password"=>"test", // Your database password
        "database"=>"test", // Your database name
        "persistentConnection"=>true,
    ]);
if ($db->getError('connectionCode')!==0) {
    die('Database connect error');
}

Database ddl operations:
$query = $db->exec(
    "CREATE TABLE test ("
        . "id int(6) NOT NULL AUTO_INCREMENT, "
        . "number int(6), "
        . "string varchar(100), "
        . "PRIMARY KEY (id) )");
if (!$query->getStatus()) {
    echo $db->getError('code') . " " . $db->getError('description');
}

Database. Execute query with binding (using universal binding, 
but you can use specific for your database style):
$query="SELECT * FROM table WHERE id=:num OR string=:num2)";
$result = $db->exec($query,['num'=>[1,'i'],'num2'=>'text']);
var_dump($result->getAll());
var_dump($result->getArray());
var_dump($result->getScalar());

Database transaction:
$db->transaction();
$db->exec("INSERT INTO table (number) VALUES (num)",['num'=>[3,'i']]); 
$db->commit(); 
// or $db->rollback();

Database. Specific for database methods:
Only for MySQL/MariaDB you can use method "getInsertId":
// MySQL binding style, but better use universal binding style
$db->exec("INSERT INTO table (number) VALUES (?)",[[3,'i']]); 
echo "id for new row is " . $db->getInsertId();
In PostgreSQL you can use another way:
// PostgreSQL binding style, but better use universal binding style
$result = $db->exec("INSERT INTO table (number) VALUES ($1) RETURNING id",[[3,'i']]);
echo "id for new row is " . $result->getScalar();