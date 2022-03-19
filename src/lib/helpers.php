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

?>
