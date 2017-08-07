# UserUploader

A PHP script executed from the command line that accepts a CSV file as input and processes the CSV file. The parsed file data is to be inserted into a MySQL database. 

## How to Run

- Place the `users.cvs` and `user_upload.php` files at the same folder, open the command line and navigate to the folder where the files are located.

- Typing `php user_upload.php` followed by different directives can produce different results:

  - Read a given CSV file and insert the data into a specified database.

    ```clike
    --file csv_file_name -u username -p password -h hostname
    ```

    Here you need to configure your MySQL user details by providing user name, password and host. 

    Example:

    ```
    > php user_upload.php --file user_upload.php -u name -p 1234 -h localhost
    ```

  - Cause the MySQL users table to be built and no further action will be taken.

    ```clike
     --create_table -u username -p password -h hostname
    ```

  - Run the script but not insert into the database. All other functions will be executed, but the database won't be altered.

    ```clike
    --dry_run --file csv_file_name
    ```

  - Output the above list of directives with details.

    ```clike
     --help
    ```

     

## Note

- The CSV file will contain user data and have three columns: name, surname, email.

- All the records in the CSV file will be preprocessed and validated before being inserted into the database. An error message will be reported when encountering invalid entries. 

  The process includes:

  - stripping unnecessary characters (extra space, tab, newline)
  - setting name and surname fields  to be capitalised e.g. from “john” to “John”
  - setting email field to be lower case
  - validating the email address

  ​
