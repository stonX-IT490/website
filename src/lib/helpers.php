<?php
session_start(); //we can start our session here so we don't need to worry about it on other pages
require_once __DIR__ . "/db.php";
//this file will contain any helpful functions we create
//I have provided two for you
function is_logged_in()
{
  return isset($_SESSION["user"]);
}

function has_role($role)
{
  if (is_logged_in() && isset($_SESSION["user"]["admin"])) {
    if( $_SESSION["user"]["admin"] == 1 ) {
      return true;
    }
  }
  return false;
}

function get_email()
{
  if (is_logged_in() && isset($_SESSION["user"]["email"])) {
    return $_SESSION["user"]["email"];
  }
  return "";
}

function get_user_id()
{
  if (is_logged_in() && isset($_SESSION["user"]["id"])) {
    return $_SESSION["user"]["id"];
  }
  return -1;
}

function get_first_name()
{
  if (is_logged_in() && isset($_SESSION["user"]["id"])) {
    return $_SESSION["user"]["first_name"];
  }
  return -1;
}

function get_last_name()
{
  if (is_logged_in() && isset($_SESSION["user"]["id"])) {
    return $_SESSION["user"]["last_name"];
  }
  return -1;
}

function get_name()
{
  if (is_logged_in() && isset($_SESSION["user"]["id"])) {
    return $_SESSION["user"]["first_name"] . " " .$_SESSION["user"]["last_name"];
  }
  return -1;
}

function safer_echo($var)
{
  if (!isset($var)) {
    echo "";
    return;
  }
  echo htmlspecialchars($var, ENT_QUOTES, "UTF-8");
}

//for flash feature
function flash($msg)
{
  if (isset($_SESSION['flash'])) {
    array_push($_SESSION['flash'], $msg);
  } else {
    $_SESSION['flash'] = [];
    array_push($_SESSION['flash'], $msg);
  }
}

function getMessages()
{
  if (isset($_SESSION['flash'])) {
    $flashes = $_SESSION['flash'];
    $_SESSION['flash'] = [];
    return $flashes;
  }
  return [];
}

//end flash

function getURL($path) {
  if(substr($path, 0, 1) == '/') {
    return $path;
  }
  return "/$path";
}

function changeBalance($db, $user, $balChange) {
  // Current Balance
  $stmt = $db->prepare("SELECT amount from Balance WHERE user_id = :id");
  $stmt->execute([":id" => $user]);
  $currentBal = $stmt->fetch(PDO::FETCH_ASSOC)['amount'];

  // Insert Transaction
  $transactions = $db->prepare(
    "INSERT INTO Transactions (user_id, amount, expected_balance)
    VALUES (:id, :amount, :expected_balance)"
  );
  $balance = $db->prepare(
    "UPDATE Balance SET amount = :balance WHERE user_id = :id"
  );

  // Calc
  $finalBalance = $currentBal + $balChange;

  $transactions->execute([
    ":id" => $user,
    ":amount" => $balChange,
    ":expected_balance" => $finalBalance
  ]);

  $balance->execute([":balance" => $finalBalance, ":id" => $user]);

  return $transactions;
}

function tradeShare($db, $user, $type, $symbol, $shareChange) {
  $shareChange = abs($shareChange);

  // Get latest stock value
  $stmt = $db->prepare("SELECT id, value FROM Stock_Data WHERE created = (SELECT max(created) FROM Stock_Data WHERE symbol = :symbol) AND symbol = :symbol");
  $r = $stmt->execute([":symbol" => $symbol]);
  if( $r ) {
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
    return [ 'error' => true, 'msg' => 'SQL ERROR' ];
  }

  // Currently Held Shares
  $stmt = $db->prepare("SELECT held_shares from Portfolio WHERE user_id = :id AND symbol = :symbol");
  $r = $stmt->execute([":id" => $user, ":symbol" => $symbol]);
  if( $r ) {
    $currentHeldShares = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
    return [ 'error' => true, 'msg' => 'SQL ERROR' ];
  }

  // Check user balance
  $stmt = $db->prepare("SELECT amount from Balance WHERE user_id = :id");
  $r = $stmt->execute([":id" => $user]);
  if( $r ) {
    $currentBal = $stmt->fetch(PDO::FETCH_ASSOC)['amount'];
  } else {
    return [ 'error' => true, 'msg' => 'SQL ERROR' ];
  }

  $stockValue = $shareChange * $currentData['value'];

  // Check if user can buy shares
  if ( $stockValue > $currentBal && $type == 'buy' ) {
    return [ 'error' => true, 'msg' => 'Not enough funds to buy shares.' ];
  }

  // Prepared Statements
  $trade = $db->prepare(
    "INSERT INTO Trade (user_id, symbol, shares, expected_shares, stock_data_id)
    VALUES (:id, :symbol, :shares, :expected_shares, :stock_data_id)"
  );
  $transactions = $db->prepare(
    "INSERT INTO Transactions (user_id, amount, expected_balance)
    VALUES (:id, :amount, :expected_balance)"
  );
  $balance = $db->prepare(
    "UPDATE Balance SET amount = :balance WHERE user_id = :id"
  );
  if ( $type == 'buy' && !$currentHeldShares ) {
    $portfolio = $db->prepare(
      "INSERT INTO Portfolio(user_id, symbol, last_trade_id, initial_trade_id, initial_shares, held_shares)
      VALUES (:id, :symbol, :trade_id, :trade_id, :shares, :shares)"
    );
  } else {
    $portfolio = $db->prepare(
      "UPDATE Portfolio SET last_trade_id = :trade_id, held_shares = :shares WHERE user_id = :id AND symbol = :symbol"
    );
  }

  if ( $type == 'buy' ) {
    $r = $trade->execute([
      ":id" => $user,
      ":symbol" => $symbol,
      ":shares" => $shareChange,
      ":expected_shares" => !$currentHeldShares ? $shareChange : $currentHeldShares['held_shares'] + $shareChange,
      ":stock_data_id" => $currentData['id'],
    ]);
    if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
    $tradeId = $db->lastInsertId();
    $r = $portfolio->execute([
      ":id" => $user,
      ":symbol" => $symbol,
      ":trade_id" => $tradeId,
      ":shares" => !$currentHeldShares ? $shareChange : $currentHeldShares['held_shares'] + $shareChange
    ]);
    if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
    $r = $transactions->execute([
      ":id" => $user,
      ":amount" => -$stockValue,
      ":expected_balance" => $currentBal - $stockValue
    ]);
    if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
    $r = $balance->execute([
      ":id" => $user,
      ":balance" => $currentBal - $stockValue
    ]);
    if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
  }

  if ( $type == 'sell' ) {
    if ( !$currentHeldShares ) {
      return [ 'error' => true, 'msg' => 'Cannot sell shares of a security not owned.' ];
    } else {
    // Check if user can sell shares
      $currentHeldShares = $currentHeldShares['held_shares'];
      if ( $currentHeldShares == 0 && $type == 'sell') {
        return [ 'error' => true, 'msg' => 'Cannot sell shares of a security not owned.' ];
      }
      if ( $shareChange > $currentHeldShares && $type == 'sell' ) {
        return [ 'error' => true, 'msg' => 'Cannot sell more shares can currently owned.' ];
      }
      $r = $trade->execute([
        ":id" => $user,
        ":symbol" => $symbol,
        ":shares" => -$shareChange,
        ":expected_shares" => $currentHeldShares - $shareChange,
        ":stock_data_id" => $currentData['id'],
      ]);
      if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
      $tradeId = $db->lastInsertId();
      $r = $portfolio->execute([
        ":id" => $user,
        ":symbol" => $symbol,
        ":trade_id" => $tradeId,
        ":shares" => $currentHeldShares - $shareChange
      ]);
      if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
      $r = $transactions->execute([
        ":id" => $user,
        ":amount" => $stockValue,
        ":expected_balance" => $currentBal + $stockValue
      ]);
      if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
      $r = $balance->execute([
        ":id" => $user,
        ":balance" => $currentBal + $stockValue
      ]);
      if( !$r ) { return [ 'error' => true, 'msg' => 'SQL ERROR' ]; }
    }
  }

  return [ 'error' => false, 'msg' => NULL ];
}

?>
