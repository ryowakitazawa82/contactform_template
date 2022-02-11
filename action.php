<?php
// var_dump($_FILES);
// exit;
//19７行目
// デバッグ関数
function dd($param) {
  echo "---------------------------------------------";
  print "<pre>";
  var_dump($param);
  exit;
}

//ドキュメントルート以下の階層を取得
$uri = $_SERVER['REQUEST_URI'];
//もし$uriの中にaction.phpという名前があったら削除
if (preg_match('/action.php/', $uri)) {
  $uri = str_replace('action.php', '', $uri);
  //$urlの最後の２文字が"//"だった場合"/"に変更する
  if (mb_substr($uri, '-2') == '//') {		//ここから
    $uri = substr_replace($uri,'', -1,1);
  }											//ここまで最後の文字が//の場合しか対応できないので修正する
}

function is_SSL(){
  if( !empty( $_SERVER['https'] ) )
  return true;

  if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
  return true;

  if ( !isset($_SERVER['SERVER_PORT']))
  return false;

  if( $_SERVER['SERVER_PORT'] == 443)
  return true;

  return false;
};

$isSSL = is_SSL();
$prot = $isSSL ? 'https://' : 'http://';
$host = $_SERVER["HTTP_HOST"];
$config['base_url'] = $prot . $host . $uri;
//LP以外からのアクセスの場合はリダイレクトする
if (empty($_POST['from_lp'])) {
  header("Location: {$config['base_url']}index.php");
  exit;
}

//セッションにpostの値をいれる
session_start();
$_SESSION = $_POST;

//入力必須項目
$required = array(
  'name',
  'name2',
  'email',
);


// dd($_POST);
//$_POST[]が空だったらindex.phpに戻る
foreach ($required as $value) {
  if(empty($_POST[$value])) {
    header("Location: {$config['base_url']}");
    var_dump($config['base_url']);
    exit;
  }
}


//電話番号が半角数字以外だったらリダイレクト
if (preg_match('/-/', $_POST['tel'])) {
  $pieces=explode('-',$_POST['tel']);
  foreach($pieces as $value){
    if(!ctype_digit($value)){
      $tel_test[]=1;
    }
  }
} else {
  if(!ctype_digit($_POST['tel'])){
    $tel_test[]=2;
  }
}
//メールアドレスがおかしかったらリダイレクト
if (!empty($_POST['email'])) {
  if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $_POST['email'])) {
    //正しいのでスルーしてOK
  } else {
    $email_test[] = 1;
  }
}

if(!empty($email_test)){
  $_SESSION['email_test'] = $email_test;
}

// if (!empty($tel_test)) {
//   if (is_array($tel_test)) {
//     exit;
//     $_SESSION['tel_test']=$tel_test;
//     header("Location: {$config['base_url']}index.php");
//     exit;
//   }
// } else if (!empty($email_test)) {
//   if (is_array($email_test)){
//     header("Location: {$config['base_url']}index.php");
//     exit;
//   }
// }
//バリデーション通ったのでセッション削除する
session_destroy();

// //一字ファイルができているか（アップロードされているか）チェック
// if(is_uploaded_file($_FILES['file']['tmp_name'])){

//   //一字ファイルを保存ファイルにコピーできたか
//   if(move_uploaded_file($_FILES['file']['tmp_name'],"./".$_FILES['file']['name'])){

//     //正常
//     //echo "uploaded";

//   }else{

//     //コピーに失敗（だいたい、ディレクトリがないか、パーミッションエラー）
//     //echo "error while saving.";
//     //exit;
//   }

// }else{

//   //そもそもファイルが来ていない。
//   echo "file not uploaded.";

// }


// 採用担当者送信メール
$to = "`@senpo-email`";
$subject = 'LPから応募が入りました';
$text = "
LPから応募が入りました。
ご確認の上、1週間以内にご連絡をお願い致します。

下記の内容でエントリーをいただいています。
==========================
氏名：
{$_POST['name']}{$_POST['name2']}

メールアドレス：
{$_POST['email']}

電話番号：
{$_POST['tel']}
==============================
";

// 回答者への自動返信メール
$toReply = $_POST['your-email'];
$subjectReply = '応募を受け付けました - 株式会社TIMIFURA';
$textReply = "こんにちは。株式会社TIMIFURAです。
この度は、ご応募いただきまして誠にありがとうございます。

1週間以内にご連絡いたしますので、
引き続きご志望のほどよろしくお願いいたします。

※下記の内容でエントリー頂いています。
==========================
氏名：
{$_POST['name']}{$_POST['name2']}

メールアドレス：
{$_POST['email']}

電話番号：
{$_POST['tel']}
==========================
";

// $file = $_FILES["file"]["name"];

// $res = sendMail($to, $subject, $text, $file);
// $resReply = sendMail($toReply, $subjectReply, $textReply, $file);
// if($res && $resReply) {
//     session_start();
//     header("Location: {$config['base_url']}thanks.php");
//     exit;
// } else {
//   header("Location: {$_SERVER['HTTP_REFERER']}");
//   exit;
// }
if(sendMail($to , $subject,  $text, $file) && sendMail($toReply , $subjectReply,  $textReply, $file)) {
  session_start();
  //header("Location: http://0.0.0.0/confirm.php");

  header("Location: {$config['base_url']}thanks.php");
  exit;
} else {
exit;
  //header("Location: http://0.0.0.0/index.php");
  header("Location: {$config['base_url']}");
  exit;
}

function sendMail( $to=null, $subject=null, $text=null, $file=null){
  //初期化
  $res = false;

  //日本語の使用宣言
  mb_language("ja");
  mb_internal_encoding("UTF-8");

  if( $to === null || $subject === null || $text === null ) {
    return false;
	}

	// 送信元の設定
    $sender_email = '@senpo-email';
    $org = '株式会社TIMIFURA';
    $from = '@senpo-email';

	// ヘッダー設定
	$header = '';
	$header .= "Content-Type: multipart/mixed;boundary=\"__BOUNDARY__\"\n";
	$header .= "Content-Type: text/plain; charset=\"ISO-2022-JP\"\n";
	//$header .= "Return-Path: " . $sender_email . " \n";
	$header .= "From: " . $from ." \n";
	$header .= "Sender: " . $from ." \n";
	$header .= "Reply-To: " . $sender_email . " \n";
	$header .= "Organization: " . $org . " \n";
	$header .= "X-Sender: " . $org . " \n";
	$header .= "X-Priority: 3 \n";

	// テキストメッセージを記述
	$body = "--__BOUNDARY__\n";
	$body .= "Content-Type: text/plain; charset=\"ISO-2022-JP\"\n\n";
	$body .= $text . "\n";
	$body .= "--__BOUNDARY__\n";

	$returnPath = "-f " . $sender_email;

  // ファイルを添付
  // if (!empty($file['name'])) {
  //   $body .= "Content-Type: application/octet-stream; name=\"{$file['name']}\"\n";
  //   $body .= "Content-Disposition: attachment; filename=\"{$file['name']}\"\n";
  //   $body .= "Content-Transfer-Encoding: base64\n";
  //   $body .= "\n";
  //   $body .= chunk_split(base64_encode(file_get_contents($file['tmp_name'])));
  //   $body .= "--__BOUNDARY__--";
  // }

	//メール送信
  //mb_send_mailは成功したら、true, 失敗したらfalseがカエル
	$res = mb_send_mail( $to, $subject, $body, $header, $returnPath);
	return $res;
}
