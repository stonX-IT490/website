<?php
ob_start();
require_once __DIR__ . "/../lib/rabbitmq-common/rabbitMQLib.php";
require_once __DIR__ . "/../partials/nav.php";
if (!has_role("Admin")) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: ../login.php"));
}

// Get Transactions
if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$client = new rabbitMQProducer('amq.direct', 'webserver');
$response = $client->send_request([
  'type' => 'getBalancesAdmin',
  'page' =>  $page
]);

if(!$response) {
  flash("Something went wrong, please try again");
  $transactions = [];
  $balanceData = [];
  $total_pages = 0;
} else if (isset($response['error']) && $response['error']) {
  flash($response['msg']);
  $transactions = [];
  $balanceData = [];
  $total_pages = 0;
} else {
  $transactions = $response['results'];
  $balanceData = $response['data'];
  $total_pages = $response['total_pages'];
}

ob_end_flush();
?>

<h3 class="text-center mt-4 mb-4">Balance (All Users)</h3>

<center>
  <div class="card" style="width: 18rem;">
    <div class="card-body">
      <h5 class="card-title text-center">$<?php safer_echo($balanceData['total']); ?></h5>
      <p class="card-text text-center">As of <?php safer_echo($balanceData['date']); ?></p>
    </div>
  </div>
</center>

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
        <td><?php echo gmp_sign((int)$r["amount"]) == -1 ? '-' : '+'; ?>$<?php safer_echo(abs($r["amount"])); ?></td>
        <td>$<?php safer_echo($r["expected_balance"]); ?></td>
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
