<?php
// database connection variables
$db_hostname = "studdb.csc.liv.ac.uk";
$db_database = "sgssing2";
$db_username = "sgssing2";
$db_password = "liv@shub95";
$db_charset  = "utf8mb4";


$dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";
$opt = array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false
);

try {
  // connection object used further to refer the database connection
  $dbconn = new PDO($dsn, $db_username, $db_password, $opt);
} catch (PDOException $err) {
  $error_message = 'Database Error: ';
  $error_message .= $err->getMessage();
  echo $error_message;
  exit();
}
?>
<?php
  // if friday midnight --> reset the avaialability in the timetable
  // and clear the reseravtions table to allow new bookings
  if (date("l") == "Saturday" && date("h:i:s A")=="00:00:00") {
    
    $query = "TRUNCATE reservations";
    $statement = $dbconn->prepare($query);
    $statement->execute();
    $statement->closeCursor();
    
    $query = "UPDATE timetable SET availability = capacity;";
    $statement = $dbconn->prepare($query);
    $statement->execute();
    $statement->closeCursor();

  }
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practicals Booking Portal</title>
  <link rel="stylesheet" href="webstyle.css?v=<?php echo time(); ?>" type="text/css">
</head>

<body>
  <main>
    <header>
      <h1>Welcome to practicals booking system!</h1>
    </header>

    <section>
      <h2>New Reservation</h2>
    </section>

    <section>
      <p><span class="error">* required field</span></p>
      <div>
        <!-- 1st form to select module -->
        <form action="practicals-submit.php" method="POST">
          <label for="modulename">Choose a module:</label>

          <!-- list all available modules from database-->
          <?php
          $query = 'SELECT DISTINCT module FROM timetable
                  WHERE availability > 0
                  ORDER BY module ASC';
          $statement = $dbconn->prepare($query);
          $statement->execute();
          $modules = $statement->fetchAll();
          $statement->closeCursor();
          ?>

          <!-- drop down list populated from the db-->
          <select name="modulename" id="modulename">
            <?php foreach ($modules as $modules) { ?>
              <option value="<?php echo $modules['module'] ?>"><?php echo $modules['module'] ?></option>
            <?php } ?>
          </select>
          <span class="error">*</span>
      </div>

      <div>
        <input class="button" name="submit" type="submit" value="Proceed">
      </div>
      </form>
    </section>
  </main>

</body>

</html>