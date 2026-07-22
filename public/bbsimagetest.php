<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合

  $image_filename = null;
  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // アップロードされた画像がある場合
    if (preg_match('/^image\//', $_FILES['image']['type']) !== 1) {
      // アップロードされたものが画像ではなかった場合処理を強制的に終了
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php");
      return;
    }

    // MIMEタイプをファイル内容から確認
     $mime = mime_content_type($_FILES['image']['tmp_name']);
    
   $allowed = [
       'image/jpeg',
           'image/png',
               'image/gif'
               ];
    if (!in_array($mime, $allowed)) {
    die('画像ファイルのみアップロードできます');
    }
    
    // 元のファイル名から拡張子を取得
    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];
    
    // 新しいファイル名を決める
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
    
    $filepath = '/var/www/upload/image/' . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);


  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // 処理が終わったらリダイレクトする
  // リダイレクトしないと，リロード時にまた同じ内容でPOSTすることになる
  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest.php");
  return;
}

// ページ数をURLクエリパラメータから取得。無い場合は1ページ目とみなす
 $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

 // 1ページあたりの行数を決める
 $count_per_page = 10;

 // ページ数に応じてスキップする行数を計算
 $skip_count = $count_per_page * ($page - 1);

 // hogehogeテーブルの行数を SELECT COUNT で取得
 $count_sth = $dbh->prepare('SELECT COUNT(*) FROM bbs_entries');

 $count_sth->execute();

 $count_all = $count_sth->fetchColumn();

 if ($skip_count >= $count_all) {
     // スキップする行数が全行数より多かったらおかしいのでエラーメッセージ表示し終了
         print('このページは存在しません!');
             return;
             }
// いままで保存してきたものを取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');

$select_sth = $dbh->prepare(
  "SELECT *
     FROM bbs_entries
        ORDER BY id ASC
           LIMIT :limit OFFSET :offset"
           );

$select_sth->bindValue(':limit', $count_per_page, PDO::PARAM_INT);
$select_sth->bindValue(':offset', $skip_count, PDO::PARAM_INT);

$select_sth->execute();

?>

<head>
  <title>画像投稿できる掲示板</title>
</head>

<!-- フォームのPOST先はこのファイル自身にする -->
<form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
  <textarea name="body" required></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image" id="image">
  </div>
  <button type="submit">送信</button>
</form>

<hr>



<?php foreach($select_sth as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd><?= $entry['id'] ?></dd>
    <dt>日時</dt>
    <dd><?= $entry['created_at'] ?></dd>
    <dt>内容</dt>
    <dd>
      <?= nl2br(htmlspecialchars($entry['body'])) // 必ず htmlspecialchars() すること ?>
      <?php if(!empty($entry['image_filename'])): // 画像がある場合は img 要素を使って表示 ?>
      <div>
        <img src="/image/<?= $entry['image_filename'] ?>" style="max-height: 10em;">
      </div>
      <?php endif; ?>
      </dd>
                                       </dl>
<?php endforeach ?>
<!-- ページ番号表示 -->

<div style="margin-top:20px; text-align:center;">

<?php
// 全ページ数を計算
 $total_pages = ceil($count_all / $count_per_page);
 ?>

 <?php for ($i = 1; $i <= $total_pages; $i++): ?>

   <?php if ($i == $page): ?>

       <strong>
             <?= $i ?>
                 </strong>
                   <?php else: ?>

                       <a href="?page=<?= $i ?>">
                             <?= $i ?>
                                 </a>

                                   <?php endif; ?>

                                   <?php endfor; ?>

                                   </div>




<script>
const imageInput = document.getElementById('image');

imageInput.addEventListener('change', function() {

    const file = this.files[0];

        if (file) {
                const maxSize = 5 * 1024 * 1024;

                        if (file.size > maxSize) {
                                    alert('5MBを超える画像はアップロードできません');
                                                this.value = '';
                                                        }
                                                            }

                                                            });
                                                            </script>

