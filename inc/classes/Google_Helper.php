<?php

namespace WpErpSync;

use Google_Service_Drive_DriveFile;
use Google_client;
use Google_Service_Drive;

class Google_Helper
{
  private $oauth_credentials;
  private $client;
  private $service;
  public $redirect_uri;
  public $tokenPath;
  public function __construct()
  {
    $this->tokenPath = GDATA_FOLDER . 'token.json';
    $this->oauth_credentials = $this->getOAuthCredentialsFile();
    $this->redirect_uri = 'http://gchaim.com/wp-admin/admin.php?page=dashboard';
    $this->client = new Google_Client();
    $this->client->setAuthConfig($this->oauth_credentials);
    $this->client->setRedirectUri($this->redirect_uri);
    $this->client->addScope("https://www.googleapis.com/auth/drive");
    $this->client->setAccessType("offline");
    if (!file_exists(GDATA_FOLDER . 'refresh-token.json')) {
      $this->client->setPrompt('select_account consent');
    }

    $this->service = new Google_Service_Drive($this->client);
    //wp hook
    add_action('init', [$this, 'register_session']);
  }

  function get_client()
  {
    return $this->client;
  }

  function get_service()
  {
    return $this->service;
  }

  function register_session()
  {
    if (!session_id())
      session_start();
  }
  function check_credentials()
  {
    if (!$this->oauth_credentials) {
      echo  $this->missingOAuth2CredentialsWarning();
    }
    return;
  }


  function missingOAuth2CredentialsWarning()
  {
    $ret = "
    <h3 class='warn'>
      Warning: You need to set the location of your OAuth2 Client Credentials from the
      <a href='http://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the wp-erp-sync/inc/gdrive_data/ directory of and
      rename them 'oauth-credentials.json'.
    </p>";

    return $ret;
  }

  function getOAuthCredentialsFile()
  {
    // oauth2 creds
    $oauth_creds = GDATA_FOLDER . 'oauth-credentials.json';
    if (file_exists($oauth_creds)) {
      return $oauth_creds;
    }
    return false;
  }

  function generate_token($code)
  {
    if (file_exists(GDATA_FOLDER . 'refresh-token.json')) {
      $token = json_decode(file_get_contents(GDATA_FOLDER . 'refresh-token.json'), true);
      $this->client->setAccessToken($token);
      $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
    } else {
      $token = $this->client->fetchAccessTokenWithAuthCode($code);
      $this->client->setAccessToken($token);
    }
    // store in the session also
    $_SESSION['upload_token'] = $token;
    if (!file_exists(dirname($this->tokenPath))) {
      mkdir(dirname($this->tokenPath), 0700, true);
    }
    file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
    $google_token = $_SESSION['upload_token'];
    if ($google_token['refresh_token'] != null) {
      file_put_contents(GDATA_FOLDER . 'refresh-token.json', json_encode($google_token));
    }
    echo '
            <script>
            setTimeout(function () {
                window.location.href= "' . $this->redirect_uri . '";
            }, 10000);
            </script>       
            ';
  }

  function get_token_from_refresh()
  {
    if (file_exists(GDATA_FOLDER . 'refresh-token.json')) {
      $accessToken = json_decode(file_get_contents(GDATA_FOLDER . 'refresh-token.json'), true);
      $this->client->setAccessToken($accessToken);
      $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
    } else {
      return $this->client->createAuthUrl();
    }
  }

  function upload_file($order, $service)
  {
    $folder = ERP_DATA_FOLDER . 'orders/';
    DEFINE("ORDER", "SITEDOC_$order.xml");
    $file = new Google_Service_Drive_DriveFile();
    $file->setDescription('Order Number ' . $order);
    $file->setName(ORDER);
    if (file_exists($folder . ORDER)) {
      $result = $service->files->create(
        $file,
        array(
          'data' => file_get_contents($folder . ORDER),
          'mimeType' => 'text/xml',
          'uploadType' => 'media'
        )
      );
      return $result;
    } else {
      return 'File ' . ORDER . ' not exists!';
    }
  }

  function get_sync_files($service)
  {
    $folderId = '1sqMX_gqttVqcdYQfufL1j-RDW6vweOmv';
    $optParams = array(
      'pageSize' => 10,
      'fields' => 'nextPageToken, files(id, name)',
      'q' => "'" . $folderId . "' in parents"
    );
    $results = $service->files->listFiles($optParams);
    if (count($results->getFiles()) == 0) {
      print "<h4>No files found in your Sync folder.</h4>";
    } else {
      print "<h3>Files in Sync folder:</h3>";
      foreach ($results->getFiles() as $file) {
        printf("<a target='_blank' href='https://drive.google.com/open?id=%s' >%s </a></br>", $file->getId(), $file->getName());
        $outHandle = fopen(ERP_DATA_FOLDER."sync/".$file->getName(), "w+");
        $content = $service->files->get($file->getId(), array('alt' => 'media'));
        while (!$content->getBody()->eof()) {
          fwrite($outHandle, $content->getBody()->read(1024));
        }
        fclose($outHandle);
      }
      echo "Download files complate.\n";
    }
  }
}
