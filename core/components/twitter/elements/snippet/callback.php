<?php
require_once MODX_CORE_PATH. 'components/library/abraham-twitter/twitteroauth/twitteroauth.php';
require_once MODX_CORE_PATH . 'components/twitter/config/config.twitter.php';

session_start();

if (!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret'])) {

    // We've got everything we need
    // TwitterOAuth instance, with two new parameters we got in twitter_login.php
    $twitteroauth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
// Let's request the access token
    $access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);

// Save it in a session var
    $_SESSION['access_token'] = $access_token;
// Let's get the user's info
    $user_info = $twitteroauth->get('account/verify_credentials');

    if(isset($user_info->error)){
        // Something's wrong, go back to square 1
        header('Location: twitter_login.php');
    } else {
        $oauth_provider = 'twitter';

        // Let's find the user by its ID
        $sql = $modx->prepare('SELECT * FROM ext_twitter_users WHERE oauth_provider = :oauth_provider AND oauth_uid = :oauth_uid');
        $sql->bindParam(':oauth_provider', $oauth_provider);
        $sql->bindParam(':oauth_uid', $user_info->id);
        $sql->execute();

        if($sql->execute()) {

            $result = $sql->fetch(PDO::FETCH_ASSOC);
        } else {
            $result = '';
            echo $sql->errorCode();
            print_r($sql->errorInfo());
        }

        // If not, let's add it to the database
        if(empty($result)) {
            $stmt = $modx->prepare('INSERT INTO ext_twitter_users (oauth_provider, oauth_uid, username, oauth_token, oauth_secret)
                VALUES (:oauth_provider, :oauth_uid, :username, :oauth_token, :oauth_secret)');

            $stmt->bindParam(':oauth_provider', $oauth_provider);
            $stmt->bindParam(':oauth_uid', $user_info->id);
            $stmt->bindParam(':username', $user_info->screen_name);
            $stmt->bindParam(':oauth_token', $access_token['oauth_token']);
            $stmt->bindParam(':oauth_secret', $access_token['oauth_token_secret']);

            if ($stmt->execute()) {
                $intLastId = $modx->lastInsertId();
            } else {
                $intLastId = '';
                echo $stmt->errorCode();
                print_r($stmt->errorInfo());
            }

            $sql = $modx->prepare('SELECT * from ext_twitter_users WHERE id = :id');
            $sql->bindParam(':id', $intLastId);
            $sql->execute();

            $result = $sql->fetch(PDO::FETCH_ASSOC);

        } else {
            $intLastId = 8;
            $sql = $modx->prepare('SELECT * from ext_twitter_users WHERE id = :id');
            $sql->bindParam(':id', $intLastId);
            $sql->execute();

            $result = $sql->fetch(PDO::FETCH_ASSOC);
        }

        $_SESSION['id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        $_SESSION['oauth_uid'] = $result['oauth_uid'];
        $_SESSION['oauth_provider'] = $result['oauth_provider'];
        $_SESSION['oauth_token'] = $result['oauth_token'];
        $_SESSION['oauth_secret'] = $result['oauth_secret'];

        //header('Location: twitter_update.php');
    }

// Print user's info

    print_r($user_info);
} else {
    // Something's missing, go back to square 1
    header('Location: twitter_login.php');
}