<?php
// include the previous page
include('practicals.php');

// assign the modulename from $_POST of previous page
// this will not process on $_SERVER['PHP_SELF] action
if (isset($_POST['modulename'])) {
    $modulename = $_POST['modulename'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practicals Booking Portal</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <main>

        <?php
        // error msg variables
        $sessionErr = $nameErr = $emailErr = "";
        // field variables for session, student name
        // and student email address 
        $sessionname = $stdname = $stdemail = "";

        // name and email check booleans
        $nameCheck = false;
        $emailCheck = false;

        // input test function
        function test_input($data)
        {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        // start when submit is clicked
        if (isset($_POST['onSubmit'])) {

            // get modulename from the current page
            $modulename = test_input($_POST['module']);

            // session details
            if (empty($_POST["sessionname"])) {
                $sessionErr = "session time not selected";
            } else {
                $sessionname = test_input($_POST["sessionname"]);
                // echo $sessionname;
            }

            // check if student name field is empty
            if (empty($_POST["stdname"])) {
                $nameErr = "name is required";
            } else {
                $stdname = test_input($_POST["stdname"]);
                // echo $stdname;

                // check that name only contains letters and whitespace
                if (!preg_match("/^[a-zA-Z-' ]*$/", $stdname)) {
                    $nameErr = "Only letters and white space allowed";
                } else {
                    // flag
                    $nameCheck = true;
                    // echo $nameCheck;
                }
            }

            // check if student email field is empty
            if (empty($_POST["stdemail"])) {
                $emailErr = "e-mail is required";
            } else {
                $stdemail = test_input($_POST["stdemail"]);
                // echo $stdemail;

                // check if a booking request already exists
                // for a given module, time and email
                $query = "SELECT rid from reservations 
                          WHERE studentEmail='$stdemail' AND module='$modulename' AND sessionTime='$sessionname'";
                $statement = $dbconn->prepare($query);
                $statement->execute();
                $exists = $statement->fetch();
                $statement->closeCursor();

                // if exists throw error
                if ($exists) {
                    $errorMessage = "Booking Unsucessful: Email already registered for the selected Module and Time!";
                } else {
                    // check if e-mail address is in correct format
                    if (!filter_var($stdemail, FILTER_VALIDATE_EMAIL)) {
                        $emailErr = "Invalid email format";
                    } else {
                        //flag
                        $emailCheck = true;
                        // echo $emailCheck;
                    }
                }
            }

            if ($nameCheck && $emailCheck && !empty($modulename) && !empty($sessionname)) {

                // check again for availability of the chosen 
                // module and time before submitting request
                $query = "SELECT id from timetable 
                          WHERE module='$modulename' AND sessionTime='$sessionname' AND availability > 0 ";
                $statement = $dbconn->prepare($query);
                $statement->execute();
                $validID = $statement->fetch();
                $statement->closeCursor();

                if ($validID) {
                    // update the reservations table with the booking details
                    $query = "INSERT INTO reservations
                            (studentName, studentEmail, module, sessionTime)
                    VALUES
                            (:stdname, :stdemail, :modulename, :sessionname)";

                    $statement = $dbconn->prepare($query);
                    $statement->bindParam(':stdname', $stdname);
                    $statement->bindParam(':stdemail', $stdemail);
                    $statement->bindParam(':modulename', $modulename);
                    $statement->bindParam(':sessionname', $sessionname);
                    $statement->execute();
                    $statement->closeCursor();

                    // update the availability in the timetable
                    $query = "UPDATE timetable
                              SET availability = availability - 1
                              WHERE module=:modulename AND sessionTime=:sessionname AND availability > 0";
                    $statement = $dbconn->prepare($query);
                    $statement->bindParam(':modulename', $modulename);
                    $statement->bindParam(':sessionname', $sessionname);
                    $statement->execute();
                    $statement->closeCursor();

                    // success message
                    $succesMessage = "Booking Sucessful!";
                    $stdname = "";
                    $stdemail = "";
                } else {
                    // if in the current session the booking has been made for the same module 
                    // and time the booking request fails to preserve integrity 
                    $errorMessage = "Booking Unsucessful: Selected Module and Time not available!";
                }
            }
        }
        ?>

        <section>
            <div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <?php
                    if (isset($_POST['module'])) {
                        $modulename = $_POST['module'];
                    }
                    ?>
                    <div>
                        <!-- show the chosen module to the user-->
                        <label for="module">Module selected: </label>
                        <select name="module" id="module">
                            <option value="<?php echo $modulename ?>"><?php echo $modulename ?></option>
                        </select>
                    </div>

                    <div>
                        <label for="sessionname">Select a time:</label>
                        <?php
                        // only select the times that correspond to $_POST['modulename']
                        $query = "SELECT sessionTime FROM timetable
                        WHERE module='$modulename' AND availability > 0";
                        $statement = $dbconn->prepare($query);
                        $statement->execute();
                        $sessions = $statement->fetchAll();
                        $statement->closeCursor();
                        ?>
                        <!-- drop down list -->
                        <select name="sessionname" id="sessionname">
                            <option value="" selected disabled hidden>
                                --select a time--
                            </option>
                            <?php foreach ($sessions as $sessions) { ?>
                                <option value="<?php echo $sessions['sessionTime'] ?>"><?php echo $sessions['sessionTime'] ?></option>
                            <?php } ?>
                        </select>
                        <span class="error">* <?php echo $sessionErr; ?></span>
                    </div>

                    <div>
                        <!-- input for name -->
                        <label for="stdname">Enter full-name:</label>
                        <input type="text" id="stdname" name="stdname" value="<?php echo $stdname; ?>">
                        <span class="error">* <?php echo $nameErr; ?></span>
                    </div>

                    <div>
                        <!-- input for email -->
                        <label for="stdemail">Enter e-mail:</label>
                        <input type="text" id="stdemail" name="stdemail" value="<?php echo $stdemail; ?>">
                        <span class="error">* <?php echo $emailErr; ?></span>
                    </div>

                    <div>
                        <input class="button" name="onSubmit" type="submit" value="Book">
                    </div>
                </form>
                <div>
                    <!-- restart booking, redirect to first page -->
                    <a href='practicals.php'><button>Start again!</button></a>
                </div>
            </div>
        </section>

        <!-- succes/failure message -->
        <p id="succesMessage"><?php if (isset($succesMessage)) {
                                    echo $succesMessage;
                                } ?></p>
        <p id="errorMessage"><?php if (isset($errorMessage)) {
                                    echo $errorMessage;
                                } ?></p>

    </main>
</body>

</html>