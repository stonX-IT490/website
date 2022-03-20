<?php
ob_start();
require_once __DIR__ . "/lib/rabbitmq-common/rabbitMQLib.php";
require_once __DIR__ . "/partials/nav.php";
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: login.php"));
}

$user = get_user_id();

// Get Stocks
if(isset($_GET["page"])){
  $page = (int)$_GET["page"];
} else {
  $page = 1;
}

$client = new rabbitMQProducer('amq.direct', 'webserver');
$response = $client->send_request([
  'type' => 'getStocks',
  'page' =>  $page
]);

if(!$response) {
  flash("Something went wrong, please try again");
  $results = [];
  $total_pages = 0;
} else if (isset($response['error']) && $response['error']) {
  flash($response['msg']);
  $results = [];
  $total_pages = 0;
} else {
  $results = $response['results'];
  $total_pages = $response['total_pages'];
}

ob_end_flush();
?>
<h3 class="text-center mt-4 mb-4">Stocks</h3>

<?php if (count($results) > 0): ?>
  <table class="table table-striped mt-4">
    <thead class="thead-dark">
      <tr>  
        <th scope="col">Symbol</th>
        <th scope="col">Share Value</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
  <?php foreach ($results as $r): ?>
      <tr>
        <th scope="row"><?php safer_echo($r["symbol"]); ?><br><small><?php safer_echo($r["company_name"]); ?></small></th>
        <td>$<?php safer_echo(abs($r["value"])); ?><br><small>As of <?php safer_echo($r["created"]); ?></small></td>
        <td>
          <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-primary">Details</a>
          <a href="<?php echo getURL('trade.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-success">Trade</a>
          <a href="<?php echo getURL('watch.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-warning">Watch</a>
          <a href="<?php echo getURL('news.php'); ?>?symbol=<?php safer_echo($r["symbol"]); ?>" class="btn btn-info">News</a>
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
