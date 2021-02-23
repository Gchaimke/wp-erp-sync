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

    $url =  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $url .= $_SERVER['HTTP_HOST'];

    $this->oauth_credentials = $this->getOAuthCredentialsFile();
    $this->check_credentials();
    $this->register_session();
    $this->tokenPath = GDATA_FOLDER . 'token.json';
    $this->redirect_uri = $url . '/wp-admin/admin.php?page=dashboard';
    $this->client = new Google_Client();
    $this->client->setApplicationName("wp-erp-sync");
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
      Logger::log_message('Token generated');
      $this->redirect_to_url($this->redirect_uri);
    } else {
      $this->get_token_from_refresh();
    }
  }

  function redirect_to_url($url, $delay = 1000)
  {
    echo '
            <script>
            setTimeout(function () {
                window.location.href= "' . $url . '";
            }, ' . $delay . ');
            </script>       
            ';
  }

  static function get_token()
  {
    $self = new static;
    if (!file_exists($self->tokenPath)) {
      $self->get_token_from_refresh();
    }
    return json_decode(file_get_contents($self->tokenPath));
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
      Logger::log_message('Token refreshed');
      return $accessTokenUpdated;
    }
    Logger::log_message('refresh token not exists', 1);
    return false;
  }

  function upload_file($order, $service)
  {
    $msg = '';
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
      $msg = ORDER . ' file uploaded';
      Logger::log_message($msg);
      return $result;
    } else {
      $msg = ORDER . ' file not exists!';
      Logger::log_message($msg, 1);
      return $msg;
    }
  }

  function try_to_sync($service)
  {
    $folderName = 'SITEEXP';
    $optParams = array(
      'pageSize' => 10,
      'fields' => 'nextPageToken, files',
      'q' => "name = '" . $folderName . "' and mimeType = 'application/vnd.google-apps.folder'"
    );
    try {
      $service->files->listFiles($optParams);
      return true;
    } catch (\Throwable $error) {
      return false;
    }
  }

  function get_sync_files($service)
  {
    $synced = 0;
    $folderName = 'SITEEXP';
    $optParams = array(
      'pageSize' => 10,
      'fields' => 'nextPageToken, files',
      'q' => "name = '" . $folderName . "' and mimeType = 'application/vnd.google-apps.folder'"
    );
    Logger::log_message('Try to sync');
    try {
      $folder =  $service->files->listFiles($optParams);
      $optParams = array(
        'pageSize' => 10,
        'fields' => 'nextPageToken, files(id, name, modifiedTime)',
        'q' => "'" . $folder[0]['id'] . "' in parents"
      );
      $files = $service->files->listFiles($optParams);
      if (count($files->getFiles()) == 0) {
        print "<h4>No files found in your Sync folder.</h4>";
      } else {
        print "<h3>Files in Sync folder:</h3>";
        foreach ($files->getFiles() as $file) {
          printf("<a target='_blank' href='https://drive.google.com/open?id=%s' >%s </a>Last modifed: %s</br>", $file->getId(), $file->getName(), $file->getModifiedTime());
          $file_path = ERP_DATA_FOLDER . "sync/" . $file->getName();
          $local_file_modifed =  NULL;
          if (file_exists($file_path)) {
            $local_file_modifed =  filemtime($file_path);
          }
          $server_file_modifed = strtotime($file->getModifiedTime());
          $time_diferece = $server_file_modifed - $local_file_modifed;
          if ($time_diferece > 500) {
            $outHandle = fopen($file_path, "w+");
            $content =  $service->files->get($file->getId(), array('alt' => 'media'));
            while (!$content->getBody()->eof()) {
              fwrite($outHandle, $content->getBody()->read(1024));
            }
            fclose($outHandle);
            Logger::log_message($file->getName() . ' Synced! Last modified Time: ' . $time_diferece);
            echo 'file sync success!<br/><br/>' . $time_diferece;
            $synced++;
          } else {
            $msg = ' last modification < 10 minutes. Not synced.';
            echo $msg . '<br/><br/>';
            Logger::log_message($file->getName() . $msg . ' difference ' . $time_diferece);
          }
        }
        Logger::log_message('New files: ' . $synced);
        return $synced;
      }
    } catch (\Throwable $error) {
      $error_array = json_decode($error->getMessage(), true);
      $error_msg = $error_array['error']['errors'][0]['message'];
      Logger::log_message($error_msg, 1);
      echo $error->getMessage();
      return -1;
    }
  }

  static function clear_sync_folder()
  {
    $dir = ERP_DATA_FOLDER . "sync/";
    $files = glob($dir . "*.XML");
    foreach ($files as $file) { // iterate files
      unlink($file); // delete file
    }
  }
}
