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

// Get Stocks
$symbol = $_GET["symbol"];
if (isset($_GET["time"])) {
  $time = $_GET["time"];
  if( $time == "6h" ) {
    $sqlTime = "6 hour";
  } else if( $time == "24h" ) {
    $sqlTime = "24 hour";
  } else if( $time == "2d" ) {
    $sqlTime = "2 day";
  } else if( $time == "7d" ) {
    $sqlTime = "7 day";
  }
} else {
  $sqlTime = "24 hour";
}

$stmt = $db->prepare(
  "SELECT *
  FROM Stock_Data
  JOIN Stocks ON Stocks.symbol = Stock_Data.symbol
  WHERE Stock_Data.symbol = :symbol
  AND created > now() - interval $sqlTime
  ORDER BY created ASC"
);
$r = $stmt->execute([':symbol' => $symbol]);
if ($r) {
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $result = [];
  flash("There was a problem fetching the results");
}

$data['labels'] = [];
$data['data'] = [];
if(count($result) > 0) {
  foreach ($result as $r) {
    array_push($data['data'], $r['value']);
    array_push($data['labels'], $r['created']);
  }
}

ob_end_flush();

?>

<?php if (count($result) > 0): ?>
<h3 class="text-center mt-4"><?php safer_echo($result[0]['company_name']); ?> (<?php safer_echo($result[0]['symbol']); ?>)</h3>
<p class="text-center mb-4">As of <?php safer_echo($result[count($result)-1]['created']); ?></p>
<?php else: ?>
<h3 class="text-center mt-4 mb-4"><?php safer_echo($symbol); ?></h3> 
<?php endif; ?>

<?php if (count($result) > 0): ?>
  <canvas id="myChart"></canvas>
  <script>
    var stockData = <?php echo json_encode($data); ?>;
    var ctx = document.getElementById('myChart').getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: stockData.labels,
        datasets: [{ 
          data: stockData.data,
          label: "<?php safer_echo($result[0]['symbol']); ?>",
        }]
      },
      options: {
        responsive: true,
      },
    });
  </script>
<?php endif; ?>

<center>
    <div class="btn-group mt-4" role="group">
      <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($symbol); ?>&time=6h" class="btn btn-secondary">6h</a>
      <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($symbol); ?>&time=24h" class="btn btn-secondary">24h</a>
      <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($symbol); ?>&time=2d" class="btn btn-secondary">2d</a>
      <a href="<?php echo getURL('stock_detail.php'); ?>?symbol=<?php safer_echo($symbol); ?>&time=7d" class="btn btn-secondary">7d</a>
    </div>
</center>

<?php require __DIR__ . "/partials/flash.php"; ?>
