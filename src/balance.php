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

// Get Balance
$stmt = $db->prepare(
  "SELECT amount, last_updated
  FROM Balance
  WHERE user_id = :q"
);
$r = $stmt->execute([":q" => $user]);
if ($r) {
  $balance = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  $balance = [];
  flash("There was a problem fetching the results");
}

// Get Transactions
if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$per_page = 10;

$stmt = $db->prepare(
  "SELECT count(*) as total
  FROM Transactions
  WHERE user_id = :q
  ORDER BY created DESC"
);
$r = $stmt->execute([':q' => $user]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if($result){
  $total = (int)$result["total"];
} else {
  $total = 0;
}

$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare(
  "SELECT created, amount, expected_balance
  FROM Transactions
  WHERE user_id = :q
  ORDER BY created DESC LIMIT :offset,:count"
);
$stmt->bindValue(":q", $user);
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
<h3 class="text-center mt-4 mb-4">Balance</h3>

<?php if (count($balance) == 2): ?>
  <center>
    <div class="card" style="width: 18rem;">
      <div class="card-body">
        <h5 class="card-title text-center">$<?php safer_echo($balance["amount"]); ?></h5>
        <p class="card-text text-center">As of <?php safer_echo($balance["last_updated"]); ?></p>
      </div>
    </div>
    <a class="btn btn-primary mt-4" href="<?php echo getURL('deposit.php'); ?>" role="button"><i class="fas fa-hand-holding-usd fa-fw"></i> Deposit</a>
  </center>
<?php endif; ?>

<?php if (count($transactions) > 0): ?>
  <table class="table table-striped mt-4">
    <thead class="thead-dark">
      <tr>  
        <th scope="col">Created</th>
        <th scope="col">Amount</th>
        <th scope="col">Balance</th>
      </tr>
    </thead>
    <tbody>
  <?php foreach ($transactions as $r): ?>
      <tr>
        <th scope="row"><?php safer_echo($r["created"]); ?></th>
        <td>$<?php safer_echo(abs($r["amount"])); ?></td>
        <td>$<?php safer_echo(abs($r["expected_balance"])); ?></td>
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
