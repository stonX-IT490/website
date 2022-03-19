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

// Get Transactions
if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$per_page = 10;

$stmt = $db->prepare(
  "SELECT count(*) as total
  FROM Trade
  WHERE user_id = :user_id"
);
$r = $stmt->execute([':user_id' => $user]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if($result){
  $total = (int)$result["total"];
} else {
  $total = 0;
}

$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare(
  "SELECT *,(shares * value) AS amount
  FROM Trade
  JOIN Stock_Data ON Stock_Data.id = Trade.stock_data_id
  JOIN Stocks ON Stocks.symbol = Trade.symbol
  WHERE user_id = :user_id
  ORDER BY Trade.created DESC LIMIT :offset,:count"
);
$stmt->bindValue(":user_id", $user);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->bindValue(":count", $per_page, PDO::PARAM_INT);
$r = $stmt->execute();
if ($r) {
  $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $transactions = [];
  flash("There was a problem fetching the results");
}

ob_end_flush();
?>
<h3 class="text-center mt-4 mb-4">Trade History</h3>

<?php if (count($transactions) > 0): ?>
  <table class="table table-striped mt-4">
    <thead class="thead-dark">
      <tr>  
        <th scope="col">Created</th>
        <th scope="col">Security</th>
        <th scope="col">Shares</th>
        <th scope="col">Amount</th>
        <th scope="col">Held Shares</th>
      </tr>
    </thead>
    <tbody>
  <?php foreach ($transactions as $r): ?>
      <tr>
        <th scope="row"><?php safer_echo($r["created"]); ?></th>
        <td><?php safer_echo($r["symbol"]); ?><br><small><?php safer_echo($r["company_name"]); ?></small></td>
        <td><?php safer_echo($r["shares"]); ?></td>
        <td>$<?php safer_echo($r["amount"]); ?></td>
        <td><?php safer_echo($r["expected_shares"]); ?></td>
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
