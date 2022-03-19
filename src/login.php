<?php require_once __DIR__ . "/partials/nav.php"; ?>

<h3 class="text-center mt-4">Login</h3>

<form method="POST">
  <div class="form-group">
    <label for="email">Email Address</label>
    <input type="email" class="form-control" id="email" name="email" maxlength="100" required>
  </div>
  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" class="form-control" id="password" maxlength="60" name="password" required>
  </div>
  <div class="btn-toolbar" role="toolbar">
    <div class="btn-group mr-2" role="group">
      <button type="submit" name="login" value="Login" class="btn btn-primary">Login</button>
    </div>
    <!--<div class="btn-group" role="group">
      <a href="reset_password.php" class="btn btn-primary" role="button">Reset Password</a>
    </div>-->
  </div>
</form>

<?php
if (isset($_POST["login"])) {
  $email = null;
  $password = null;
  if (isset($_POST["email"])) {
    $email = $_POST["email"];
  }
  if (isset($_POST["password"])) {
    $password = $_POST["password"];
  }
  $isValid = true;
  if (!isset($email) || !isset($password)) {
    $isValid = false;
    flash("Email or password missing");
  }
  if (!strpos($email, "@")) {
    $isValid = false;
    //echo "<br>Invalid email<br>";
    flash("Invalid email");
  }
  if ($isValid) {
    $db = getDB();
    if (isset($db)) {
      $stmt = $db->prepare(
        "SELECT id, email, password, first_name, last_name, admin from Users WHERE email = :email LIMIT 1"
      );

      $params = [":email" => $email];
      $r = $stmt->execute($params);
      //echo "db returned: " . var_export($r, true);
      $e = $stmt->errorInfo();
      if ($e[0] != "00000") {
        //echo "uh oh something went wrong: " . var_export($e, true);
        flash("Something went wrong, please try again");
      }
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($result && isset($result["password"])) {
        $password_hash_from_db = $result["password"];
        if (password_verify($password, $password_hash_from_db)) {
          unset($result["password"]); //remove password so we don't leak it beyond this page
          //let's create a session for our user based on the other data we pulled from the table
          $_SESSION["user"] = $result; //we can save the entire result array since we removed password
          //on successful login let's serve-side redirect the user to the home page.
          flash("Log in successful");
          die(header("Location: home.php"));
        } else {
          flash("Invalid password");
        }
      } else {
        flash("Invalid user");
      }
    }
  } else {
    flash("There was a validation issue");
  }
}

require __DIR__ . "/partials/flash.php";
?>
