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

// Get user accounts
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
  $balance = $_POST["balance"];
  $memo = $_POST["memo"];
  
  if($type == 'deposit') {
    $account = $_POST["account"];
    $r = changeBalance($db, 1, $account, 'deposit', $balance, $memo);
  }
  if($type == 'withdraw')  {
    $account = $_POST["account"];
    $stmt = $db->prepare('SELECT balance FROM Accounts WHERE id = :id');
    $stmt->execute([':id' => $account]);
    $acct = $stmt->fetch(PDO::FETCH_ASSOC);
    if($acct["balance"] < $balance) {
      flash("Not enough funds to withdraw!");
      die(header("Location: transaction.php?type=withdraw"));
    }
    $r = changeBalance($db, $account, 1, 'withdraw', $balance, $memo);
  }
  if($type == 'transfer')  {
    $account_src = $_POST["account_src"];
    $account_dest = $_POST["account_dest"];
    if($account_src == $account_dest){
      flash("Cannot transfer to same account!");
      die(header("Location: transaction.php?type=transfer"));
    }
    $stmt = $db->prepare('SELECT balance FROM Accounts WHERE id = :id');
    $stmt->execute([':id' => $account_src]);
    $acct = $stmt->fetch(PDO::FETCH_ASSOC);
    if($acct["balance"] < $balance) {
      flash("Not enough funds to transfer!");
      die(header("Location: transaction.php?type=transfer"));
    }
    $r = changeBalance($db, $account_src, $account_dest, 'transfer', $balance, $memo);
  }
  
  if ($r) {
    flash("Successfully executed transaction.");
  } else {
    flash("Error doing transaction!");
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
      <label for="account">Security</label>
      <select class="form-control" id="account" name="security">
        <?php foreach ($results as $r): ?>
        <?php if ($r["account_type"] != "loan"): ?>
        <option value="<?php safer_echo($r["id"]); ?>" <?php echo $symbol == $r["symbol"] ? 'selected' : ''; ?>>
          <?php safer_echo($r["symbol"]); ?> | <?php safer_echo($r["company_name"]); ?> | <?php safer_echo($r["value"]); ?> (As of <?php safer_echo($r["created"]); ?>)
        </option>
        <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="deposit">Amount</label>
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">Shares</span>
        </div>
        <input type="number" class="form-control" id="deposit" min="1" name="balance" step="1" placeholder="1"/>
      </div>
    </div>
    <button type="submit" name="save" value="Do Transaction" class="btn btn-success">Do Transaction</button>
  </form>
<?php endif; ?>

<?php require __DIR__ . "/partials/flash.php"; ?>
