<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {

    $image_filename = null;

    if (!empty($_POST['image_base64'])) {

        $base64 = preg_replace(
            '/^data:image\/jpeg;base64,/',
            '',
            $_POST['image_base64']
        );

        $image_binary = base64_decode($base64);

        if ($image_binary === false) {
            die('画像の処理に失敗しました');
        }

        $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.jpg';

        $filepath = '/var/www/upload/image/' . $image_filename;

        file_put_contents($filepath, $image_binary);

    } elseif (
        isset($_FILES['image']) &&
        !empty($_FILES['image']['tmp_name'])
    ) {

        $mime = mime_content_type($_FILES['image']['tmp_name']);

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/gif'
        ];

        if (!in_array($mime, $allowed)) {
            die('画像ファイルのみアップロードできます');
        }

        $pathinfo = pathinfo($_FILES['image']['name']);
        $extension = $pathinfo['extension'];

        $image_filename =
            strval(time()) .
            bin2hex(random_bytes(25)) .
            '.' .
            $extension;

        $filepath =
            '/var/www/upload/image/' .
            $image_filename;

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $filepath
        );
    }

    $insert_sth = $dbh->prepare(
        "INSERT INTO bbs_entries
        (body, image_filename)
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

$page = isset($_GET['page'])
    ? intval($_GET['page'])
    : 1;

$count_per_page = 10;

$skip_count =
    $count_per_page * ($page - 1);

$count_sth = $dbh->prepare(
    'SELECT COUNT(*) FROM bbs_entries'
);

$count_sth->execute();

$count_all = $count_sth->fetchColumn();

if (
    $count_all > 0 &&
    $skip_count >= $count_all
) {
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

<body>

<form
    method="POST"
    action="./bbsimagetest.php"
    enctype="multipart/form-data"
>

    <textarea
        name="body"
        required
    ></textarea>

    <div style="margin: 1em 0;">

        <input
            type="file"
            accept="image/*"
            name="image"
            id="imageInput"
        >

    </div>

    <div
        id="previewArea"
        style="
            display: none;
            margin: 15px 0;
        "
    >

        <p
            style="
                margin-bottom: 8px;
                font-weight: bold;
            "
        >
            選択中の画像
        </p>

        <div
            style="
                width: 400px;
                min-height: 100px;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 12px;
                background: #f8f8f8;
                text-align: center;
                box-sizing: border-box;
            "
        >

            <canvas
                id="previewCanvas"
                style="
                    max-width: 100%;
                    max-height: 300px;
                    border-radius: 8px;
                "
            ></canvas>

        </div>

        <button
            type="button"
            id="cancelImage"
            style="
                margin-top: 10px;
                padding: 6px 14px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            "
        >
            画像を取り消す
        </button>

    </div>

    <input
        id="imageBase64Input"
        type="hidden"
        name="image_base64"
    >

    <canvas
        id="imageCanvas"
        style="display: none;"
    ></canvas>

    <button type="submit">
        送信
    </button>

</form>

<hr>

<?php foreach ($select_sth as $entry): ?>

    <dl
        style="
            margin-bottom: 1em;
            padding-bottom: 1em;
            border-bottom: 1px solid #ccc;
        "
    >

        <dt
            id="entry<?= htmlspecialchars($entry['id']) ?>"
        >
            ID
        </dt>

        <dd>
            <?= htmlspecialchars($entry['id']) ?>
        </dd>

        <dt>
            日時
        </dt>

        <dd>
            <?= htmlspecialchars($entry['created_at']) ?>
        </dd>

        <dt>
            内容
        </dt>

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

<div
    style="
        margin-top: 20px;
        text-align: center;
    "
>

<?php

$total_pages =
    ceil($count_all / $count_per_page);

?>

<?php for (
    $i = 1;
    $i <= $total_pages;
    $i++
): ?>

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

    const imageInput =
        document.getElementById("imageInput");

    const imageBase64Input =
        document.getElementById("imageBase64Input");

    const imageCanvas =
        document.getElementById("imageCanvas");

    const previewArea =
        document.getElementById("previewArea");

    const previewCanvas =
        document.getElementById("previewCanvas");

    const cancelImage =
        document.getElementById("cancelImage");


    imageInput.addEventListener("change", () => {

        if (imageInput.files.length < 1) {

            previewArea.style.display = "none";

            return;
        }


        const file =
            imageInput.files[0];


        if (file.size > 5 * 1024 * 1024) {

            alert(
                "5MB以下のファイルを選択してください。"
            );

            imageInput.value = "";

            previewArea.style.display = "none";

            return;
        }


        if (!file.type.startsWith("image/")) {

            alert(
                "画像ファイルを選択してください。"
            );

            imageInput.value = "";

            previewArea.style.display = "none";

            return;
        }


        const reader =
            new FileReader();

        const image =
            new Image();


        reader.onload = () => {

            image.onload = () => {


                /* プレビュー用canvas */

                const previewContext =
                    previewCanvas.getContext("2d");


                previewCanvas.width =
                    image.naturalWidth;

                previewCanvas.height =
                    image.naturalHeight;


                previewContext.drawImage(
                    image,
                    0,
                    0
                );


                previewArea.style.display =
                    "block";


                /* 縮小用canvas */

                const originalWidth =
                    image.naturalWidth;

                const originalHeight =
                    image.naturalHeight;

                const maxLength =
                    2000;


                if (
                    originalWidth <= maxLength &&
                    originalHeight <= maxLength
                ) {

                    imageCanvas.width =
                        originalWidth;

                    imageCanvas.height =
                        originalHeight;

                } else if (
                    originalWidth > originalHeight
                ) {

                    imageCanvas.width =
                        maxLength;

                    imageCanvas.height =
                        maxLength *
                        originalHeight /
                        originalWidth;

                } else {

                    imageCanvas.width =
                        maxLength *
                        originalWidth /
                        originalHeight;

                    imageCanvas.height =
                        maxLength;
                }


                const context =
                    imageCanvas.getContext("2d");


                context.drawImage(
                    image,
                    0,
                    0,
                    imageCanvas.width,
                    imageCanvas.height
                );


                imageBase64Input.value =
                    imageCanvas.toDataURL(
                        "image/jpeg",
                        0.9
                    );

            };


            image.src =
                reader.result;

        };


        reader.readAsDataURL(file);

    });


    /* 画像を取り消す */

    cancelImage.addEventListener(
        "click",
        () => {

            imageInput.value = "";

            imageBase64Input.value = "";


            const context =
                previewCanvas.getContext("2d");


            context.clearRect(
                0,
                0,
                previewCanvas.width,
                previewCanvas.height
            );


            previewArea.style.display =
                "none";

        }
    );

});

</script>

</body>

