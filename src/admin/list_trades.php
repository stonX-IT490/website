<?php
ob_start();
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
  'type' => 'getTradesAdmin',
  'page' =>  $page
]);

if(!$response) {
  flash("Something went wrong, please try again");
  $trades = [];
  $tradesData = [];
  $total_pages = 0;
} else if (isset($response['error']) && $response['error']) {
  flash($response['msg']);
  $trades = [];
  $tradesData = [];
  $total_pages = 0;
} else {
  $trades = $response['results'];
  $tradesData = $response['data'];
  $total_pages = $response['total_pages'];
}

ob_end_flush();
?>

<h3 class="text-center mt-4 mb-4">Trades (All Users)</h3>

<center>
  <div class="card" style="width: 18rem;">
    <div class="card-body">
      <h5 class="card-title text-center"><?php safer_echo($tradesData["total"]); ?></h5>
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
