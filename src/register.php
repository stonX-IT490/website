<?php
require_once __DIR__ . "/partials/nav.php";
if (isset($_POST["register"])) {
  $email = null;
  $password = null;
  $confirm = null;
  $first_name = null;
  $last_name = null;
  if (isset($_POST["email"])) {
    $email = $_POST["email"];
  }
  if (isset($_POST["password"])) {
    $password = $_POST["password"];
  }
  if (isset($_POST["confirm"])) {
    $confirm = $_POST["confirm"];
  }
  if (isset($_POST["first_name"])) {
    $first_name = $_POST["first_name"];
  }
  if (isset($_POST["last_name"])) {
    $last_name = $_POST["last_name"];
  }
  $isValid = true;
  //check if passwords match on the server side
  if ($password != $confirm) {
    flash("Passwords don't match!");
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
      $stmt = $db->prepare(
        "INSERT INTO Users(email, password, first_name, last_name) VALUES(:email, :password, :first_name, :last_name)"
      );
      //here's the data map for the parameter to data
      $params = [
        ":email" => $email,
        ":password" => $hash,
        ":first_name" => $first_name,
        ":last_name" => $last_name
      ];
      $r = $stmt->execute($params);
      $e = $stmt->errorInfo();
      if ($e[0] == "00000") {
        $stmt = $db->prepare('SELECT id FROM Users WHERE email = :email');
        $r = $stmt->execute([':email' => $email]);
        if($r) {
          $id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
          $stmt = $db->prepare('INSERT INTO Balance(user_id) VALUES(:user_id)');
          $stmt->execute([':user_id' => $id]);
        }
        flash("Successfully registered! Please login.");
      } else {
        if ($e[0] == "23000") {
          //code for duplicate entry
          flash("Email already exists!");
        } else {
          flash("An error occurred, please try again.");
        }
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
<h3 class="text-center mt-4">Register</h3>

<form method="POST">
  <div class="form-group">
    <label for="email">Email Address</label>
    <input type="email" class="form-control" id="email" name="email" maxlength="100" required value="<?php safer_echo($email); ?>">
  </div>
  <div class="row">
    <div class="col-sm">
      <div class="form-group">
        <label for="first_name">First Name</label>
        <input type="text" class="form-control" id="first_name" name="first_name" maxlength="60" required placeholder="John">
      </div>
    </div>
    <div class="col-sm">
      <div class="form-group">
        <label for="last_name">Last Name</label>
        <input type="text" class="form-control" id="last_name" name="last_name" maxlength="60" required placeholder="Smith">
      </div>
    </div>
  </div>
  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" class="form-control" id="password" maxlength="60" name="password" required>
  </div>
  <div class="form-group">
    <label for="confirm">Confirm Password</label>
    <input type="password" class="form-control" id="confirm" maxlength="60" name="confirm" required>
  </div>
  <button type="submit" name="register" value="Register" class="btn btn-primary">Register</button>
</form>

<?php require __DIR__ . "/partials/flash.php"; ?>
