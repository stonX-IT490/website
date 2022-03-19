<?php
header("Location: ../index.php");

require_once(__DIR__ . "/partials/nav.php");

if (isset($_POST["reset"])) {
  $email = null;
  $password = null;
  $confirm = null;
  if (isset($_POST["email"])) {
    $email = $_POST["email"];
  }
  if (isset($_POST["password"])) {
    $password = $_POST["password"];
  }
  if (isset($_POST["confirm"])) {
    $confirm = $_POST["confirm"];
  }
  
  $isValid = true;
   //check if passwords match on the server side
  if ($password != $confirm) {
    flash("Passwords don't match");
    $isValid = false;
  }
  if (!isset($email) || !isset($password) || !isset($confirm)) {
    $isValid = false;
  }
  //TODO other validation as desired, remember this is the last line of defense
  if ($isValid) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $db = getDB();
    if (isset($db)) {
      //here we'll use placeholders to let PDO map and sanitize our data
      $stmt = $db->prepare("UPDATE Users set password = :password WHERE email = :email");
      //here's the data map for the parameter to data
      $params = array(":email" => $email, ":password" => $hash);
      $r = $stmt->execute($params);
      $e = $stmt->errorInfo();
      if ($e[0] == "00000") {
        flash("Successfully reset password! Please login.");
      } else {
        flash("An error occurred, please try again!");
      }
    }
  } else {
    flash("There was a validation issue.");
  }
}
//safety measure to prevent php warnings
if (!isset($email)) {
    $email = "";
}
if (!isset($username)) {
    $username = "";
}
?>
<h3 class="text-center mt-4">Reset Password</h3>

<form method="POST">
  <div class="form-group">
    <label for="email">Email Address</label>
    <input type="email" class="form-control" id="email" name="email" maxlength="100" required value="<?php safer_echo($email); ?>">
  </div>
  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" class="form-control" id="password" maxlength="60" name="password" required>
  </div>
  <div class="form-group">
    <label for="confirm">Confirm Password</label>
    <input type="password" class="form-control" id="confirm" maxlength="60" name="confirm" required>
  </div>
  <button type="submit" name="reset" value="Reset" class="btn btn-primary">Reset</button>
</form>

<?php require __DIR__ . "/partials/flash.php"; ?>
