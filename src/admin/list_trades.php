<?php
ob_start();
require_once __DIR__ . "/../partials/nav.php";
if (!has_role("Admin")) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: ../login.php"));
}

$db = getDB();

$r = $db->query("SELECT count(*) as total, MAX(created) as date FROM Trade");
if ($r) {
  $tradesData = $r->fetch(PDO::FETCH_ASSOC);
  $total = (int)$tradesData["total"];
} else {
  $tradesData = [];
  $total = 0;
  flash("There was a problem fetching the results");
}

// Get Transactions
if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$per_page = 10;

$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare(
  "SELECT id, created, symbol, shares, commission_id
  FROM Trade
  ORDER BY created DESC LIMIT :offset,:count"
);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->bindValue(":count", $per_page, PDO::PARAM_INT);
$r = $stmt->execute();
if ($r) {
  $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $trades = [];
  flash("There was a problem fetching the results");
}
ob_end_flush();
?>

<h3 class="text-center mt-4 mb-4">Trades (All Users)</h3>

<center>
  <div class="card" style="width: 18rem;">
    <div class="card-body">
      <h5 class="card-title text-center"><?php safer_echo($total); ?></h5>
      <p class="card-text text-center">As of <?php safer_echo($tradesData["date"]); ?></p>
    </div>
  </div>
</center>

<?php if (count($trades) > 0): ?>
  <table class="table table-striped mt-4">
    <thead class="thead-dark">
      <tr>  
        <th scope="col">Created</th>
        <th scope="col">Symbol</th>
        <th scope="col">Shares</th>
        <th scope="col">Commission</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($trades as $r): ?>
      <tr>
        <th scope="row"><?php safer_echo($r["created"]); ?></th>
        <td><?php safer_echo($r["symbol"]); ?></td>
        <td><?php safer_echo($r["shares"]); ?></td>
        <td><?php echo ( $r["commission_id"] == NULL ? '0%' : '' ); ?></td>
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

<?php require __DIR__ . "/../partials/flash.php"; ?>
