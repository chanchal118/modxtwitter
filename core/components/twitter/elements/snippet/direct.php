<?php
require_once MODX_CORE_PATH. 'components/library/abraham-twitter/twitteroauth/twitteroauth.php';
require_once MODX_CORE_PATH . 'components/twitter/config/config.twitter.php';

session_start();

$intLastId = 8;
$sql = $modx->prepare('SELECT * from ext_twitter_users WHERE id = :id');
$sql->bindParam(':id', $intLastId);
$sql->execute();

$result = $sql->fetch(PDO::FETCH_ASSOC);

$_SESSION['id'] = $result['id'];
$_SESSION['username'] = $result['username'];
$_SESSION['oauth_uid'] = $result['oauth_uid'];
$_SESSION['oauth_provider'] = $result['oauth_provider'];
$_SESSION['oauth_token'] = $result['oauth_token'];
$_SESSION['oauth_secret'] = $result['oauth_secret'];

$twitteroauth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_secret']);



$user_info = $twitteroauth->get('account/verify_credentials');
echo '<pre>';
print_r($user_info);
echo '</pre>';