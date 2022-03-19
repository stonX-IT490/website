<?php
ob_start();
require_once __DIR__ . "/partials/nav.php";
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: login.php"));
}
ob_end_flush();
?>
    <div class="jumbotron mt-4 text-center">
      <div class="display-4">Welcome, <?php safer_echo(get_name()); ?>!</div>
      <hr class="my-4">
      <p class="lead">What would you like to do today?</p>
      <p>
        <a class="btn btn-primary" href="<?php echo getURL("portfolio.php"); ?>" role="button"><i class="fas fa-chart-line fa-fw"></i> Portfolio</a>
        <a class="btn btn-primary" href="<?php echo getURL("stocks.php"); ?>" role="button"><i class="fas fa-stream fa-fw"></i> Stocks</a>
        <a class="btn btn-primary" href="<?php echo getURL("trade.php"); ?>" role="button"><i class="fas fa-file-invoice-dollar fa-fw"></i> Trade</a>
        <a class="btn btn-primary" href="<?php echo getURL("balance.php"); ?>" role="button"><i class="fas fa-wallet fa-fw"></i> Balance</a>
      </p>
    </div>
<?php require __DIR__ . "/partials/flash.php"; ?>
