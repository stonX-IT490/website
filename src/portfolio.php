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

$stmt = $db->prepare(
  "SELECT count(*) as total, max(Trade.created) as last
  FROM Portfolio
  JOIN Trade ON Trade.id = Portfolio.last_trade_id
  WHERE Portfolio.user_id = :user_id AND held_shares != 0"
);
$r = $stmt->execute([':user_id' => $user]);
if ($r) {
  $data = $stmt->fetch(PDO::FETCH_ASSOC);
  $total = (int)$data["total"];
} else {
  $data = [];
  $total = 0;
  flash("There was a problem fetching the results");
}

if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$per_page = 10;

$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare(
  "SELECT *, Stock_Data.created AS updated, (Stock_Data.value * Portfolio.held_shares) AS totalValue
  FROM Portfolio
  JOIN Stocks ON Stocks.symbol = Portfolio.symbol
  JOIN Stock_Data ON Stock_Data.symbol = Portfolio.symbol
  WHERE user_id = :user_id
  AND held_shares != 0
  AND (Portfolio.symbol, Stock_Data.created) IN (SELECT symbol, max(created) FROM Stock_Data GROUP BY symbol)
  ORDER BY totalValue DESC LIMIT :offset,:count"
);
$stmt->bindValue(":user_id", $user);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->bindValue(":count", $per_page, PDO::PARAM_INT);
$r = $stmt->execute();
if ($r) {
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $results = [];
  flash("There was a problem fetching the results");
}

$sum = 0;
foreach($results as $r) {
  $sum += $r['held_shares'] * $r['value'];
}

ob_end_flush();
?>
<h3 class="text-center mt-4 mb-4">Portfolio</h3>

<center>
  <div class="card" style="width: 18rem;">
    <div class="card-body">
      <h5 class="card-title text-center">$<?php safer_echo($sum); ?></h5>
      <p class="card-text text-center">As of <?php safer_echo($data["last"]); ?></p>
    </div>
  </div>
</center>

<?php if (count($results) > 0): ?>
  <table class="table table-striped mt-4">
    <thead class="thead-dark">
      <tr>  
        <th scope="col">Symbol</th>
        <th scope="col">Shares</th>
        <th scope="col">Value</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
      <tr>
        <th scope="row"><?php safer_echo($r["symbol"]); ?><br><small><?php safer_echo($r["company_name"]); ?></small></th>
        <td><?php safer_echo($r["held_shares"]); ?></td>
        <td>$<?php safer_echo($r["totalValue"]); ?><br><small>As of <?php safer_echo($r["updated"]); ?></small></td>
        <td>
          <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-primary">Details</a>
          <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-success">Trade</a>
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
<?php endif; ?>

<?php require __DIR__ . "/partials/flash.php"; ?>
