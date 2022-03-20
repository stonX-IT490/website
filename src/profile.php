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

//save data if we submitted the form
if (isset($_POST["saved"])) {
  $client = new rabbitMQProducer('amq.direct', 'webserver');
  $response = $client->send_request([
    "type" => 'updateProfile',
    "email" =>  get_email(),
    "new_email" => $_POST["email"],
    "id" => get_user_id(),
    "first_name" => $_POST["first_name"],
    "last_name" => $_POST["last_name"],
    "password" => !empty($_POST["password"]) ? $_POST["password"] : '',
    "confirm" => !empty($_POST["confirm"]) ? $_POST["confirm"] : '',
  ]);
  if(!$response) {
    flash("Something went wrong, please try again");
    die(header("Location: profile.php"));
  } else if (isset($response['error']) && $response['error']) {
    flash($response['msg']);
    die(header("Location: profile.php"));
  } else {
    foreach( $response['msgs'] as $msg ) {
      flash($msg);
    }
    $result = $response['result'];
    $email = $result["email"];
    //let's update our session too
    $_SESSION["user"]["email"] = $email;
    $_SESSION["user"]["first_name"] = $result["first_name"];
    $_SESSION["user"]["last_name"] = $result["last_name"];
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
