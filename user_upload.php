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
$dbname = "userdb";

//define command line options (directives)
$shortOpts = "u:p:h:";
$longOpts = ["file:", "create_table", "dry_run", "help"];
$opts = getopt($shortOpts, $longOpts);

//notify the user when no arguments have been attached
if (!$opts) {
    echo "Missing argument(s). Use argument '--help' to see the list of directives.\n";
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

//read file and insert into database
if (isset($opts["file"]) && !isset($opts["dry_run"])) {
    if (readCSV($opts["file"])) echo "Read file successfully.\n";
    if (insertDatabase()) echo "Inserted into database successfully.\n";
}

//dry run (without modifying database)
if (isset($opts["dry_run"]) ) {
    if (isset($opts["file"]) ) {
        if (readCSV($opts["file"])) echo "Read file successfully.\n";
    }
    else echo "Oops! You need to specify the file name as well using --file [filename].\n";
}

//create table
if (isset($opts["create_table"])) {
     createTable();
}

//print help information
if (isset($opts["help"])) {
    printHelp();
}



function readCSV($fileName) {
    global $dataArray;
    $file = fopen($fileName, "r")  
        or die("Unable to open file. Please check the existence or permission of the file.\n");
    
    //read line by line
    while (!feof($file)) {

        //preprocess and validate each line of data
        $oneRecord = fgetcsv($file);

        //skip the title line ("name", "surname", "email")
        if (strtolower(trim($oneRecord[0])) === "name")
            continue;

        //preprocess and validate data before inserting to database
        $filterResult = dataFilter($oneRecord);
        if ($filterResult === "passed") {
            array_push($dataArray, $oneRecord);
        }
        else exit($filterResult);

    }
    
    fclose($file);
    return true;
}

function dataFilter(&$record) {
    $filterResult = "";
    //strip unnecessary characters (extra space, tab, newline)
    //name
    $record[0] = trim($record[0]);
    //surname
    $record[1] = trim($record[1]);
    //email
    $record[2] = trim($record[2]);

    echo "Processing record: " . $record[0] . " " . $record[1] . " " . $record[2] . "\n";

    //check if empty entry exists
    if (empty($record[0]) || empty($record[1]) || empty($record[2]) ) {
        $filterResult = "Reading file stopped: Name, surname or email cannot be empty.\n"
                    . "Please make sure the CSV file has correct data formats.\n";
    }

    //validate email format
    else if (!filter_var($record[2], FILTER_VALIDATE_EMAIL)) {
        $filterResult = "Reading file stopped: Email format is not valid.\n"
                    . "Please make sure the CSV file has correct data formats.\n";
    }

    //process name and surname format using capitalisation
    //process email format by forcing lowercase
    else {
        $record[0] = ucfirst($record[0]);
        $record[1] = ucfirst($record[1]);
        $record[2] = strtolower($record[2]);

        $filterResult = "passed";
    }

    return $filterResult;
}

function insertDatabase() {
    //create a table named "users" inside database 'userdb' if it doesn't exist
    createTable();

    //create MySQL server connection
    global $username, $password, $host, $dbname;
    $conn = @mysqli_connect($host, $username, $password, $dbname)
        or die("Oops! Failed to connect to MySQL server. " . mysqli_connect_error() . "\n");
        
    //iterate through the CSV rows and insert each record into the table
    global $dataArray;
    $arrlength = count($dataArray);

    for ($i = 1; $i < $arrlength; $i++) {
        $oneRecord = $dataArray[$i];
        //escapes special characters in a string for use in an SQL statement
        $name = mysqli_real_escape_string($conn, $oneRecord[0]);
        $surname = mysqli_real_escape_string($conn, $oneRecord[1]);
        $email = mysqli_real_escape_string($conn, $oneRecord[2]);

        $sql = <<<EOT
            REPLACE INTO users (name, surname, email)
            VALUES ('$name', '$surname', '$email');
EOT;
        $result = @mysqli_query($conn, $sql)
            or die("Error inserting data. " . mysqli_error($conn) . "\n");
        static $counter = 0;
        $counter++;
    }
    echo "Inserted $counter rows successfully.\n" 
        . "Duplicate records (if any) have overwritten existing ones.\n";

    mysqli_close($conn);


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
            or die("Oops! Failed to connect to MySQL server. " . mysqli_connect_error() . "\n");

        //create a database if it doesn't exist
        $sql = "SHOW DATABASES LIKE 'userdb'";
        $result = @mysqli_query($conn, $sql) 
            or die("Error querying database. " . mysqli_error($conn) . "\n");
        
        if (mysqli_num_rows($result) == 0) {
            $sql = "CREATE DATABASE userdb";
            $result = @mysqli_query($conn, $sql) 
                or die("Error creating database. " . mysqli_error($conn) . "\n");
            echo "Created database 'userdb' successfully.\n";
        }
        else echo "Found database 'userdb'.\n";

        //select the database
        @mysqli_select_db($conn, "userdb")
            or die("Cannot select database 'userdb'. " . mysqli_error($conn) . "\n");
        
        //create a table if it doesn't exist
        $sql = <<<_EOT
	        SHOW TABLES LIKE 'users';
_EOT;
        $result = @mysqli_query($conn, $sql)
            or die("Error querying the users table. " . mysqli_error($conn) . "\n");
        
        if (mysqli_num_rows($result) == 0) {
            $sql = <<<EOT
            CREATE TABLE users (
                name    varchar(30) NOT NULL,
                surname varchar(30) NOT NULL,
                email   varchar(50) NOT NULL,
                UNIQUE(email)
                )
EOT;
            $result = @mysqli_query($conn, $sql)
                or die("Error creating table 'users' " . mysqli_error($conn) . "\n");
            echo "Created table 'users' successfully.\n";
        }
        else echo "Found table 'users'.\n";

        mysqli_close($conn);
        
    }
}


function printHelp() {
    echo <<<EOT
    -----------------------------
    --file csv_file_name -u username -p password -h hostname
    Read a given CSV file and insert the data into a specified database.
    -u, -p, -h: see below.

    --create_table -u username -p password -h hostname
    This will cause the MySQL users table to be built and no further action will be taken.
    -u, -p, -h: see below.

    --dry_run --file csv_file_name
    This will be used with the --file directive in the instance that 
    we want to run the script but not insert into the DB. 
    All other functions will be executed, but the database won't be altered.

    -u username
    Specify MySQL username. 
    Used with --file or --create_table. 

    -p password
    Specify MySQL password. 
    Used with --file or --create_table. 

    -h hostname
    Specify MySQL host. 
    Used with --file or --create_table. 

    --help
    Output the above list of directives with details.
    -----------------------------\n\n
EOT;
}

?>