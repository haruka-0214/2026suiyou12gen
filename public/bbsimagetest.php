('mysql:host=mysql;dbname=example_db', 'root', '');

// POSTされた場合
if (isset($_POST['body'])) {

    $image_filename = null;

    // 画像がアップロードされた場合
    if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {

        // MIMEタイプをファイル内容から確認
        $mime = mime_content_type($_FILES['image']['tmp_name']);

        // 画像以外の場合は処理を終了
        if (preg_match('/^image\//', $mime) !== 1) {
            header("HTTP/1.1 302 Found");
            header("Location: ./bbsimagetest.php");
            return;
        }

        // 許可するMIMEタイプ
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

        // 重複しないように時間＋乱数でファイル名を作成
        $image_filename = time() . bin2hex(random_bytes(25)) . '.' . $extension;

        // 保存先
        $filepath = '/var/www/upload/image/' . $image_filename;

        // ファイルを保存
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $filepath
        );
    }

    // データベースに投稿を登録
    $insert_sth = $dbh->prepare(
        "INSERT INTO bbs_entries (body, image_filename)
         VALUES (:body, :image_filename)"
    );

    $insert_sth->execute([
        ':body' => $_POST['body'],
        ':image_filename' => $image_filename,
    ]);

    // リダイレクト
    // リロード時の二重投稿を防ぐ
    header("HTTP/1.1 302 Found");
    header("Location: ./bbsimagetest.php");
    return;
}


// ページ番号を取得
// 指定がなければ1ページ目
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// 1ページあたりの表示件数
$count_per_page = 10;

// ページに応じてスキップする件数を計算
$skip_count = $count_per_page * ($page - 1);


// 投稿の総件数を取得
$count_sth = $dbh->prepare(
    'SELECT COUNT(*) FROM bbs_entries'
);

$count_sth->execute();

$count_all = $count_sth->fetchColumn();


// 存在しないページの場合
if ($count_all > 0 && $skip_count >= $count_all) {
    print('このページは存在しません!');
    return;
}


// 投稿を取得
// 新しい投稿から表示
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


// 投稿本文をHTMLとして表示するための関数
function bodyFilter(string $body): string
{
    // HTMLタグなどをエスケープ
    $body = htmlspecialchars($body);

    // 改行を<br>に変換
    $body = nl2br($body);

    // >>1 のようなレスアンカーをリンクに変換
    // htmlspecialchars()によって「>>」は「&gt;&gt;」になっている
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

<!-- 投稿フォーム -->
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
            id="image"
        >
    </div>

    <button type="submit">送信</button>

</form>

<hr>


<!-- 投稿一覧 -->
<?php foreach ($select_sth as $entry): ?>

    <dl
        style="
            margin-bottom: 1em;
            padding-bottom: 1em;
            border-bottom: 1px solid #ccc;
        "
    >

        <!-- ID -->
        <dt id="entry<?= htmlspecialchars($entry['id']) ?>">
            ID
        </dt>

        <dd>
            <?= htmlspecialchars($entry['id']) ?>
        </dd>


        <!-- 投稿日時 -->
        <dt>日時</dt>

        <dd>
            <?= htmlspecialchars($entry['created_at']) ?>
        </dd>


        <!-- 投稿内容 -->
        <dt>内容</dt>

        <dd>

            <?= bodyFilter($entry['body']) ?>


            <!-- 画像がある場合 -->
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


<!-- ページ番号表示 -->

<div style="margin-top: 20px; text-align: center;">

<?php

// 全ページ数を計算
$total_pages = ceil($count_all / $count_per_page);

?>

<?php for ($i = 1; $i <= $total_pages; $i++): ?>

    <?php if ($i == $page): ?>

        <!-- 現在のページ -->
        <strong>
            <?= $i ?>
        </strong>

    <?php else: ?>

        <!-- その他のページ -->
        <a href="?page=<?= $i ?>">
            <?= $i ?>
        </a>

    <?php endif; ?>

<?php endfor; ?>

</div>


<script>

document.addEventListener("DOMContentLoaded", () => {

    const imageInput = document.getElementById("image");

    imageInput.addEventListener("change", () => {

        // ファイルが選択されていない場合
        if (imageInput.files.length < 1) {
            return;
        }

        // 5MBを超えている場合
        if (imageInput.files[0].size > 5 * 1024 * 1024) {

            alert("5MB以下のファイルを選択してください。");

            // ファイル選択を解除
            imageInput.value = "";
        }

    });

});

</script>

