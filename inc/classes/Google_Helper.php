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
    $this->oauth_credentials = $this->getOAuthCredentialsFile();
    $this->check_credentials();
    $this->register_session();
    $this->tokenPath = GDATA_FOLDER . 'token.json';
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
      die();
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
    $oauth_creds = GDATA_FOLDER . 'oauth-credentials.json';
    if (file_exists($oauth_creds)) {
      return $oauth_creds;
    }
    return false;
  }

  function generate_token($code = '')
  {
    if ($code != '') {
      $token = $this->client->fetchAccessTokenWithAuthCode($code);
      $this->client->setAccessToken($token);
      $_SESSION['upload_token'] = $token;
      $google_token = $_SESSION['upload_token'];
      if ($google_token['refresh_token'] != null) {
        file_put_contents(GDATA_FOLDER . 'refresh-token.json', json_encode($google_token));
      }
      $this->redirect_to_url($this->redirect_uri);
    } else {
      $this->get_token_from_refresh();
    }
  }

  function redirect_to_url($url)
  {
    echo '
            <script>
            setTimeout(function () {
                window.location.href= "' . $url . '";
            }, 1000);
            </script>       
            ';
  }

  function get_token_from_refresh()
  {
    if (file_exists(GDATA_FOLDER . 'refresh-token.json')) {
      $refreshToken = json_decode(file_get_contents(GDATA_FOLDER . 'refresh-token.json'), true);
      $this->client->setAccessToken($refreshToken);
      $refreshTokenSaved = $this->client->getRefreshToken();
      // update access token
      $this->client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
      // pass access token to some variable
      $accessTokenUpdated = $this->client->getAccessToken();
      // append refresh token
      $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;
      //Set the new acces token
      $accessToken = $refreshTokenSaved;
      $this->client->setAccessToken($accessToken);
      // save to file
      file_put_contents($this->tokenPath, json_encode($accessTokenUpdated));
      $_SESSION['upload_token'] = $accessTokenUpdated;
      return true;
    }
    Logger::log_message('refresh token not exists', 1);
    return false;
  }

  function upload_file($order)
  {
    $msg = '';
    $folder = ERP_DATA_FOLDER . 'orders/';
    DEFINE("ORDER", "SITEDOC_$order.xml");
    $file = new Google_Service_Drive_DriveFile();
    $file->setDescription('Order Number ' . $order);
    $file->setName(ORDER);

    if (file_exists($folder . ORDER)) {
      $result = $this->get_service()->files->create(
        $file,
        array(
          'data' => file_get_contents($folder . ORDER),
          'mimeType' => 'text/xml',
          'uploadType' => 'media'
        )
      );
      $msg = ORDER . ' file uploaded';
      Logger::log_message($msg);
      return $result;
    } else {
      $msg = ORDER . ' file not exists!';
      Logger::log_message($msg, 1);
      return $msg;
    }
  }

  function get_sync_files()
  {
    $folderId = '1sqMX_gqttVqcdYQfufL1j-RDW6vweOmv';
    $optParams = array(
      'pageSize' => 10,
      'fields' => 'nextPageToken, files(id, name)',
      'q' => "'" . $folderId . "' in parents"
    );
    try {
      $results =  $this->service->files->listFiles($optParams);
      if (count($results->getFiles()) == 0) {
        print "<h4>No files found in your Sync folder.</h4>";
      } else {
        print "<h3>Files in Sync folder:</h3>";
        foreach ($results->getFiles() as $file) {
          printf("<a target='_blank' href='https://drive.google.com/open?id=%s' >%s </a></br>", $file->getId(), $file->getName());
          $outHandle = fopen(ERP_DATA_FOLDER . "sync/" . $file->getName(), "w+");
          $content =  $this->service->files->get($file->getId(), array('alt' => 'media'));
          while (!$content->getBody()->eof()) {
            fwrite($outHandle, $content->getBody()->read(1024));
          }
          fclose($outHandle);
          Logger::log_message($file->getName().' Synced!');
        }
        Logger::log_message('Sync files complate');
        return;
      }
    } catch (\Throwable $th) {
      Logger::log_message('Sync files Error, folder not exists!', 1);
    }
  }
}
