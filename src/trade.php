<?php
ob_start();
require_once __DIR__ . "/partials/nav.php";
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: login.php"));
}

if (isset($_GET["type"])) {
  $type = $_GET["type"];
} else {
  $type = 'buy';
}

// init db
$user = get_user_id();
$db = getDB();

// Get stocks
$r = $db->query(
  "SELECT *
  FROM Stock_Data
  JOIN Stocks ON Stocks.symbol = Stock_Data.symbol
  WHERE (Stock_Data.symbol, created) IN (SELECT symbol, max(created) FROM Stock_Data GROUP BY symbol)
  ORDER BY Stock_Data.symbol ASC"
);
$results = $r->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET["symbol"])) {
  $symbol = $_GET["symbol"];
} else {
  $symbol = '';
}

if (isset($_POST["save"])) {
  $shares = $_POST["shares"];
  if ($shares) {
    $symbol = $_POST["security"];

    if($type == 'buy') {
      $r = tradeShare($db, $user, 'buy', $symbol, $shares);
    }

    if($type == 'sell') {
      $r = tradeShare($db, $user, 'sell', $symbol, $shares);
    }
    
    if (!$r || !$r['error']) {
      flash("Successfully executed transaction.");
    } else {
      flash("Error doing transaction: " . $r['msg']);
    }
  } else {
    flash("Please enter the amount of shares.");
  }
}
ob_end_flush();
?>

<h3 class="text-center mt-4">Trade</h3>

<ul class="nav nav-pills justify-content-center mt-4 mb-2">
  <li class="nav-item"><a class="nav-link <?php echo $type == 'buy' ? 'active' : ''; ?>" href="?type=buy<?php safer_echo( $symbol != '' ? "&symbol=$symbol" : '' ); ?>">Buy</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $type == 'sell' ? 'active' : ''; ?>" href="?type=sell<?php safer_echo( $symbol != '' ? "&symbol=$symbol" : '' ); ?>">Sell</a></li>
</ul>

<?php if (count($results) > 0): ?>
  <form method="POST">
    <div class="form-group">
      <label for="security">Security</label>
      <select class="form-control" id="security" name="security">
        <?php foreach ($results as $r): ?>
        <option value="<?php safer_echo($r["symbol"]); ?>" <?php echo $symbol == $r["symbol"] ? 'selected' : ''; ?>>
          <?php safer_echo($r["symbol"]); ?> | <?php safer_echo($r["company_name"]); ?> | <?php safer_echo($r["value"]); ?> (As of <?php safer_echo($r["created"]); ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="shares">Amount</label>
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">Shares</span>
        </div>
        <input type="number" class="form-control" id="shares" min="1" name="shares" step="1" placeholder=""/>
      </div>
    </div>
    <button type="submit" name="save" value="Do Transaction" class="btn btn-success">Do Transaction</button>
  </form>
<?php endif; ?>

<?php require __DIR__ . "/partials/flash.php"; ?>
