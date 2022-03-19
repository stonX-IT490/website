<?php
ob_start();
require_once __DIR__ . "/partials/nav.php";
//Note: we have this up here, so our update happens before our get/fetch
//that way we'll fetch the updated data and have it correctly reflect on the form below
//As an exercise swap these two and see how things change
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You must be logged in to access this page");
  die(header("Location: login.php"));
}

$db = getDB();
//save data if we submitted the form
if (isset($_POST["saved"])) {
  $isValid = true;
  //check if our email changed
  $newEmail = get_email();
  if (get_email() != $_POST["email"]) {
    //TODO we'll need to check if the email is available
    $email = $_POST["email"];
    $stmt = $db->prepare(
      "SELECT COUNT(1) as InUse from Users where email = :email"
    );
    $stmt->execute([":email" => $email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $inUse = 1; //default it to a failure scenario
    if ($result && isset($result["InUse"])) {
      try {
        $inUse = intval($result["InUse"]);
      } catch (Exception $e) {
      }
    }
    if ($inUse > 0) {
      flash("Email already in use");
      //for now we can just stop the rest of the update
      $isValid = false;
    } else {
      $newEmail = $email;
    }
  }
  if ($isValid) {
    $stmt = $db->prepare(
      "UPDATE Users set email = :email, first_name = :first_name, last_name = :last_name where id = :id"
    );
    $r = $stmt->execute([
      ":email" => $newEmail,
      ":id" => get_user_id(),
      ":first_name" => $_POST["first_name"],
      ":last_name" => $_POST["last_name"]
    ]);
    if ($r) {
      flash("Updated profile");
    } else {
      flash("Error updating profile");
    }
    //password is optional, so check if it's even set
    //if so, then check if it's a valid reset request
    if (!empty($_POST["password"]) && !empty($_POST["confirm"])) {
      if ($_POST["password"] == $_POST["confirm"]) {
        $password = $_POST["password"];
        $hash = password_hash($password, PASSWORD_BCRYPT);
        //this one we'll do separate
        $stmt = $db->prepare(
          "UPDATE Users set password = :password where id = :id"
        );
        $r = $stmt->execute([":id" => get_user_id(), ":password" => $hash]);
        if ($r) {
          flash("Reset Password.");
        } else {
          flash("Error resetting password!");
        }
      }
    }
    //fetch/select fresh data in case anything changed
    $stmt = $db->prepare(
      "SELECT email, username, first_name, last_name, privacy from Users WHERE id = :id LIMIT 1"
    );
    $stmt->execute([":id" => get_user_id()]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
      $email = $result["email"];
      $username = $result["username"];
      //let's update our session too
      $_SESSION["user"]["email"] = $email;
      $_SESSION["user"]["first_name"] = $result["first_name"];
      $_SESSION["user"]["last_name"] = $result["last_name"];
    }
  } else {
    //else for $isValid, though don't need to put anything here since the specific failure will output the message
  }
}
ob_end_flush();
?>

<h3 class="text-center mt-4">Profile</h3>

<form method="POST">
  <div class="form-group">
    <label for="email">Email Address</label>
    <input type="email" class="form-control" id="email" name="email" maxlength="100" required value="<?php safer_echo(get_email()); ?>">
  </div>
  <div class="row">
    <div class="col-sm">
      <div class="form-group">
        <label for="first_name">First Name</label>
        <input type="text" class="form-control" id="first_name" name="first_name" maxlength="60" required value="<?php safer_echo(get_first_name()); ?>">
      </div>
    </div>
    <div class="col-sm">
      <div class="form-group">
        <label for="last_name">Last Name</label>
        <input type="text" class="form-control" id="last_name" name="last_name" maxlength="60" required value="<?php safer_echo(get_last_name()); ?>">
      </div>
    </div>
  </div>

  <hr>
  <h4 class="text-center">Change Password</h4>

  <!-- DO NOT PRELOAD PASSWORD-->
  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" class="form-control" id="password" name="password" maxlength="60">
  </div>
  <div class="form-group">
    <label for="confirm">Confirm Password</label>
    <input type="password" class="form-control" id="confirm" name="confirm" maxlength="60">
  </div>
  <button type="submit" name="saved" value="Save Profile" class="btn btn-primary">Save Profile</button>
</form>

<?php require __DIR__ . "/partials/flash.php"; ?>
