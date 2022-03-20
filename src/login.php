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
require_once __DIR__ . "/lib/rabbitmq-common/rabbitMQLib.php";
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
    $client = new rabbitMQProducer('amq.direct', 'webserver');
    $response = $client->send_request([ 'type' => 'login', 'email' => $email, 'password' => $password ]);
    if(!$response) {
      flash("Something went wrong, please try again");
    } elseif ($response['error']) {
      flash($response['msg']);
    } else {
      $_SESSION["user"] = $response;
      flash("Log in successful");
      die(header("Location: home.php"));
    }
  } else {
    flash("There was a validation issue");
  }
}

require __DIR__ . "/partials/flash.php";
?>
