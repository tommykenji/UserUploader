<?php
/*
Author: Jing C. 
*/

//an array for storing data read from a CSV file
$dataArray = array();

//MySQL settings
$username = "";
$password = "";
$host = "";

//define command line options (directives)
$shortOpts = "u:p:h:";
$longOpts = ["file::", "create_table", "dry_run", "help"];
$opts = getopt($shortOpts, $longOpts);

//notify the user when no arguments have been attached
if (!$opts) {
    echo "Missing argument(s). Use argument '--help' to see the list of directives.\n";
}

if (isset($opts["file"]) && !isset($opts["dry_run"])) {
    if (readCSV()) echo "Read file successfully.\n";
    if (insertDatabase()) echo "Inserted into database successfully.\n";
}

//dry run
if (isset($opts["dry_run"]) && isset($opts["file"])) {
    if (readCSV()) echo "Read file successfully.\n";
}

//set MySQL user name
if (isset($opts["u"])) {
    global $username;
    $username = $opts["u"];
    echo "MySQL user name $username is set.\n";
}

//set MySQL password
if (isset($opts["p"])) {
    global $password;
    $password = $opts["p"];
    echo "MySQL password $password is set.\n";
}

//set MySQL host
if (isset($opts["h"])) {
    global $host;
    $host = $opts["h"];
    echo "MySQL host $host is set.\n";
}

if (isset($opts["create_table"])) {
     createTable();
}

//print help information
if (isset($opts["help"])) {
    printHelp();
}




function readCSV() {
    global $dataArray;
    $file = fopen("users.csv", "r")  
        or die("Unable to open file. Please check the existence or permission of the file.\n");
    
    //read line by line
    while (!feof($file)) {
        array_push($dataArray, fgetcsv($file));
    }
    
    fclose($file);
    return true;
}

function insertDatabase() {
    //create a table named "users" if it doesn't exist
    createTable();

}

function createTable() {
    //check whether the MySQL user name, password and host have been given
    global $username, $password, $host;
    if ( empty(trim($username)) ) {
        echo "Oops! You need to set the user name of MySQL.\n";
    }
    else if ( empty(trim($password)) ) {
        echo "Oops! You need to set the password of MySQL.\n";
    }
    else if ( empty(trim($host)) ) {
        echo "Oops! You need to set the host of MySQL.\n";
    }
    else {
        //create MySQL server connection
        $conn = @mysqli_connect($host, $username, $password) 
            or die("Oops! Failed to connect to MySQL server.\n");

        //create a database if it doesn't exist
        $sql = "SHOW DATABASES LIKE 'userdb'";
        $result = @mysqli_query($conn, $sql) 
            or die("Error querying database.");
        if (empty($result)) {
            $sql = "CREATE DATABASE userdb";
            $result = @mysqli_query($conn, $sql) 
                or die("Error creating database");
        }
        echo "Connected to database 'userdb' successfully.\n";

        //create a table if it doesn't exist
        $sql = "SELECT email FROM users";
        $result = @mysqli_query($conn, $sql)
            or die("Error querying the users table");
        if (empty($result)) {
            $sql = <<<EOT
            CREATE TABLE users (
                name    varchar(30) NOT NULL,
                surname varchar(30) NOT NULL,
                email   varchar(50) PRIMARY KEY
                )
EOT;
        $result = @mysqli_query($conn, $sql)
            or die("Error creating table 'users'");
        }
        echo "Locating table 'users' successfully.\n";
    }
}


function printHelp() {
    echo <<<EOT
    -----------------------------
    --file [csv file name] 
    This is the name of the CSV to be parsed

    --create_table 
    This will cause the MySQL users table to be built 
    (and no further action will be taken)

    --dry_run 
    This will be used with the --file directive in the instance that 
    we want to run the script but not insert into the DB. 
    All other functions will be executed, but the database won't be altered.

    -u
    MySQL username

    -p
    MySQL password

    -h
    MySQL host

    --help
    Output the above list of directives with details.
    -----------------------------\n\n
EOT;
}

?>