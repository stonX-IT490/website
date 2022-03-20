<?php
ob_start();
require_once __DIR__ . "/lib/rabbitmq-webDmzHost/rabbitMQLib.php";
require_once __DIR__ . "/partials/nav.php";
if (!is_logged_in()) {
  //this will redirect to login and kill the rest of this script (prevent it from executing)
  flash("You don't have permission to access this page");
  die(header("Location: login.php"));
}

// Get Stocks
if(!isset($_GET["symbol"])) {
  flash("No symbol given.");
  die(header("Location: index.php"));
}

$symbol = $_GET["symbol"];

$client = new rabbitMQProducer('amq.direct', 'news');
$response = $client->send_request([
  'symbol' =>  $symbol,
]);

if(!$response) {
  flash("Something went wrong, please try again");
  $result = [];
} else if (isset($response['error']) && $response['error']) {
  flash($response['msg']);
  $result = [];
} else {
  $result = $response;
}

ob_end_flush();
?>

<h3 class="text-center mt-4 mb-4"><?php safer_echo($symbol); ?> - News</h3>

<?php if (count($result) > 0): ?>
  <?php foreach ($result as $news): ?>
  <div class="card mb-4">
    <!--<img src="<?php safer_echo($news['image']); ?>" class="card-img-top" alt="...">-->
    <div class="card-body">
      <h5 class="card-title"><?php safer_echo($news['title']); ?></h5>
      <p class="card-text"><?php safer_echo($news['snippet']); ?></p>
      <a href="<?php safer_echo($news['link']); ?>" class="btn btn-primary">Go to article</a>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . "/partials/flash.php"; ?>
