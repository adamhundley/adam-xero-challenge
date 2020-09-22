<?php
ini_set('display_errors', 'On');
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once('storage.php');
require_once('taskDispatch.php');

$storage = new StorageClass();
$xeroTenantId = (string)$storage->getSession()['tenant_id'];

if ($storage->getHasExpired()) {
    $provider = new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => $_ENV['CLIENT_ID'],
        'clientSecret'            => $_ENV['CLIENT_SECRET'],
        'redirectUri'             => $_ENV['REDIRECT_URI'],
        'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
        'urlAccessToken'          => 'https://identity.xero.com/connect/token',
        'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
    ]);

    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $storage->getRefreshToken()
    ]);

    // Save my token, expiration and refresh token
    $storage->setToken(
        $newAccessToken->getToken(),
        $newAccessToken->getExpires(),
        $xeroTenantId,
        $newAccessToken->getRefreshToken(),
        $newAccessToken->getValues()["id_token"]
    );
}

$config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken((string)$storage->getSession()['token']);
$apiInstance = new XeroAPI\XeroPHP\Api\AccountingApi(
    new GuzzleHttp\Client(),
    $config
);

$message = '';
if (isset($_GET['action']) && $_GET["action"] === 'run_task') {
    $xero_tenant_id = $storage->getXeroTenantId();
    $task = new TaskDispatch($storage->getXeroTenantId(), $apiInstance);

    $item_data = [
        'Surfboard' => [
            'name' => 'Surfboard',
            'price' => 520.99,
            'quantity' => 4,
        ],
        'Skateboard' => [
            'name' => 'Skateboard',
            'price' => 124.30,
            'quantity' => 5,
        ],
    ];

    $task->createItems($item_data);
    $task->createContact();
    $task->getAccount();
    // $task->createLineItems();
    // $task->createInvoice();
    // $task->createPayment();
}
?>
<html>

<body>
    <ul>
        <li><a href="authorizedResource.php?action=run_task">Run Xero Task</a></li>
    </ul>
    <div>
        <?php
        echo ($message);
        ?>
    </div>
</body>

</html>