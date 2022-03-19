<?php
ob_start();
require_once __DIR__ . "/partials/nav.php";
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: login.php"));
}

$db = getDB();
$user = get_user_id();

if (isset($_REQUEST['symbol'])) {
  $symbol = $_REQUEST['symbol'];
  $stmt = $db->prepare('INSERT INTO Watching(user_id, symbol) VALUES(:user_id, :symbol)');
  $r = $stmt->execute([':user_id' => $user, ':symbol' => $symbol]);
  if($r) {
    flash("Added $symbol to your watch list.");
  } else {
    flash("An error occurred, please try again.");
  }
}

if (isset($_POST['unwatch'])) {
  $id = $_POST['unwatch'];
  $stmt = $db->prepare('DELETE FROM Watching WHERE id = :id');
  $r = $stmt->execute([':id' => $id]);
  if($r) {
    flash("Removed from watch list.");
  } else {
    flash("An error occurred, please try again.");
  }
}


// Get Stocks
if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$per_page = 10;

$stmt = $db->query("SELECT count(*) as total FROM Watching");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if($result){
  $total = (int)$result["total"];
} else {
  $total = 0;
}

$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare(
  "SELECT *, Watching.id AS watch_id
  FROM Watching
  JOIN Stocks ON Stocks.symbol = Watching.symbol
  JOIN Stock_Data ON Stock_Data.symbol = Watching.symbol
  WHERE (Watching.symbol, created) IN (SELECT symbol, max(created) FROM Stock_Data GROUP BY symbol)
  AND user_id = :user_id
  ORDER BY Watching.symbol ASC"
);
$r = $stmt->execute([':user_id' => $user]);
if ($r) {
  $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $transactions = [];
  flash("There was a problem fetching the results");
}

ob_end_flush();
?>
<h3 class="text-center mt-4 mb-4">Watchlist</h3>

<?php if (count($transactions) > 0): ?>
  <table class="table table-striped mt-4">
    <thead class="thead-dark">
      <tr>  
        <th scope="col">Symbol</th>
        <th scope="col">Share Value</th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($transactions as $r): ?>
      <tr>
        <th scope="row"><?php safer_echo($r["symbol"]); ?><br><small><?php safer_echo($r["company_name"]); ?></small></th>
        <td>$<?php safer_echo(abs($r["value"])); ?><br><small>As of <?php safer_echo($r["created"]); ?></small></td>
        <td>
          <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-primary">Details</a>
          <a href="<?php echo getURL('trade.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-success">Trade</a>
        </td>
        <td>
          <form method="post">
            <button class="btn btn-danger" name="unwatch" value="<?php safer_echo($r["watch_id"]); ?>">Unwatch</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <nav>
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($page - 1) < 1 ? "disabled" : ""; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Previous</a>
        </li>
        <?php for($i = 0; $i < $total_pages; $i++): ?>
          <li class="page-item <?php echo ($page-1) == $i ? "active" : ""; ?>"><a class="page-link" href="?page=<?php echo ($i + 1); ?>"><?php echo ($i + 1); ?></a></li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($page) >= $total_pages ? "disabled" : ""; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
        </li>
    </ul>
  </nav>
<?php else: ?>
  <h4 class="text-center">Watchlist is empty.</h3>
<?php endif; ?>

<?php require __DIR__ . "/partials/flash.php"; ?>
