<?php
ob_start();
require_once __DIR__ . "/lib/rabbitmq-common/rabbitMQLib.php";
require_once __DIR__ . "/partials/nav.php";
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: login.php"));
}

// init db
$user = get_user_id();

// Get user accounts
if (isset($_POST["save"])) {
  $balance = abs($_POST["balance"]);

  if( $balance > 0 ) {
    $client = new rabbitMQProducer('amq.direct', 'webserver');
    $response = $client->send_request([
      'type' => 'changeBalance',
      'user' =>  $user,
      'balance' => $balance
    ]);
    
    if(!$response) {
      flash("Something went wrong, please try again");
    } else if (isset($response['error']) && $response['error']) {
      flash($response['msg']);
    } else {
      flash("Successfully executed transaction.");
    }
  } else {
    flash("Minimum deposit has to be greater than $0");
  }
}
ob_end_flush();
?>

<h3 class="text-center mt-4">Deposit</h3>

<form method="POST">
  <div class="form-group">
    <label for="deposit">Amount</label>
    <div class="input-group">
      <div class="input-group-prepend">
        <span class="input-group-text">$</span>
      </div>
      <input type="number" class="form-control" id="deposit" min="0.00" name="balance" step="0.01" placeholder="0.00"/>
    </div>
    <small id="depositHelp" class="form-text text-muted">Maximum $5,000 per day</small>
  </div>
  <button type="submit" name="save" value="Do Transaction" class="btn btn-success">Do Transaction</button>
</form>

<?php require __DIR__ . "/partials/flash.php"; ?>
