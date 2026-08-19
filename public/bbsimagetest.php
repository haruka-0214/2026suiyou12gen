<?php

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {

  $image_filename = null;

  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {

    if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php");
      return;
    }

    $allowed = [
      'image/jpeg',
      'image/png',
      'image/gif'
    ];

    $mime = mime_content_type($_FILES['image']['tmp_name']);

    if (!in_array($mime, $allowed)) {
      die('画像ファイルのみアップロードできます');
    }

    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];

    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;

    $filepath = '/var/www/upload/image/' . $image_filename;

    move_uploaded_file(
      $_FILES['image']['tmp_name'],
      $filepath
    );
  }

  $insert_sth = $dbh->prepare(
    "INSERT INTO bbs_entries (body, image_filename)
     VALUES (:body, :image_filename)"
  );

  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest.php");
  return;
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$count_per_page = 10;

$skip_count = $count_per_page * ($page - 1);

$count_sth = $dbh->prepare(
  'SELECT COUNT(*) FROM bbs_entries'
);

$count_sth->execute();

$count_all = $count_sth->fetchColumn();

if ($count_all > 0 && $skip_count >= $count_all) {
  print('このページは存在しません!');
  return;
}

$select_sth = $dbh->prepare(
  "SELECT *
   FROM bbs_entries
   ORDER BY id DESC
   LIMIT :limit OFFSET :offset"
);

$select_sth->bindValue(
  ':limit',
  $count_per_page,
  PDO::PARAM_INT
);

$select_sth->bindValue(
  ':offset',
  $skip_count,
  PDO::PARAM_INT
);

$select_sth->execute();

function bodyFilter(string $body): string
{
  $body = htmlspecialchars($body);
  $body = nl2br($body);

  $body = preg_replace(
    '/&gt;&gt;(\d+)/',
    '<a href="#entry$1">&gt;&gt;$1</a>',
    $body
  );

  return $body;
}

?>

<head>
  <title>画像投稿できる掲示板</title>
</head>

<form
  method="POST"
  action="./bbsimagetest.php"
  enctype="multipart/form-data"
>

  <textarea name="body" required></textarea>

  <div style="margin: 1em 0;">
    <input
      type="file"
      accept="image/*"
      name="image"
      id="imageInput"
    >
  </div>

  <button type="submit">送信</button>

</form>

<hr>

<?php foreach ($select_sth as $entry): ?>

  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">

    <dt id="entry<?= htmlspecialchars($entry['id']) ?>">
      ID
    </dt>

    <dd>
      <?= htmlspecialchars($entry['id']) ?>
    </dd>

    <dt>日時</dt>

    <dd>
      <?= htmlspecialchars($entry['created_at']) ?>
    </dd>

    <dt>内容</dt>

    <dd>

      <?= bodyFilter($entry['body']) ?>

      <?php if (!empty($entry['image_filename'])): ?>

        <div>
          <img
            src="/image/<?= htmlspecialchars($entry['image_filename']) ?>"
            style="max-height: 10em;"
          >
        </div>

      <?php endif; ?>

    </dd>

  </dl>

<?php endforeach; ?>

<div style="margin-top: 20px; text-align: center;">

<?php

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

document.addEventListener("DOMContentLoaded", () => {

  const imageInput = document.getElementById("imageInput");

  imageInput.addEventListener("change", () => {

    if (imageInput.files.length < 1) {
      return;
    }

    if (imageInput.files[0].size > 5 * 1024 * 1024) {

      alert("5MB以下のファイルを選択してください。");

      imageInput.value = "";
    }

  });

});

</script>


