<?
$device_authkey = "a123456789";
if (isset($_REQUEST['authkey'])) {
    $device_authkey = $_REQUEST['authkey'];
}
$ip_list = array(
    "123.143.154.13",
    "::1");
if (1 || in_array($_SERVER['REMOTE_ADDR'], $ip_list)) {
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            .red_star {
                color: red;
            }

            .container {
                margin-top: 50px;
                top: 30px;
            }

            #quick_button {
                position: fixed;
                top: 0px;
                left: 0px;
                height: 30px;
                width: 100%;
                background: #665874;
            }

            #quick_button a {
                text-decoration: none;
            }

            #quick_button button {
                font-size: 16px;
                margin-left: 3px;
                margin-top: 3px;
                height: 22px;
            }
        </style>
        <script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
        <script type="text/javascript"
                src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/3.51/jquery.form.min.js"></script>
        <script type="text/javascript" src="//developers.kakao.com/sdk/js/kakao.min.js"></script>
        <script type="text/javascript">
        </script>
    </head>

    <body>
    <div class="container">
        <div id="quick_button">
            <a href="#device">
                <button>Device</button>
            </a>
            <a href="#sms">
                <button>Sms</button>
            </a>

            <a href="#">
                <button>맨위로이동</button>
            </a>
            <br>
        </div>

        device_authkey : <?= $device_authkey ?><br/>
        <hr id="device">
        <h1>Device</h1>

        <h3><span class="red_star">★</span>/api/device/create [[post]] 신규 디바이스 등록</h3>
        <form action="/api/device/create" method="post">
            device_id <input type="text" name="device_id" value=""> * <br>
            model_name <input type="text" name="model_name" value=""> * <br>
            platform <input type="text" name="platform" value=""> * (0-unkown, 1-ios, 2-android, 3-web)<br>
            os_ver <input type="text" name="os_ver" value=""> (운영체제 버전)<br>
            <input type="submit">
        </form>

        <h3><span class="red_star">★</span>/api/device/update_push_info [[post]] 푸쉬 토큰 업데이트</h3>
        <form action="/api/device/update_push_info" method="post">
            push_token <input type="text" name="push_token" value=""> * <br>
            <input type="submit">
        </form>

        <hr id="sms">
        <h1>Sms 서비스 </h1>

        <h3><span class="red_star">★</span>/api/phone/request_verify_code [[post]] 전화번호 인증요청</h3>
        <form action="/api/phone/request_verify_code" method="post">
            auth_token <input type="text" name="auth_token" value="<?=$device_authkey?>"> * <br>
            phone_number <input type="text" name="phone_number" value=""> * <br>
            <input type="submit">
        </form>
        <h3><span class="red_star">★</span>/api/phone/check_verify_code [[post]] 전화번호 인증확인</h3>
        <form action="/api/phone/check_verify_code" method="post">
            auth_token <input type="text" name="auth_token" value="<?=$device_authkey?>"> * <br>
            <!--        phone_number <input type="text" name="phone_number" value=""> * <br>-->
            phone_verify_key_id <input type="text" name="phone_verify_key_id" value=""> * <br>
            public_key <input type="text" name="public_key" value=""> * <br>
            private_key <input type="text" name="private_key" value=""> * <br>
            <input type="submit">
        </form>

        <hr id="sms">
        <h1>Test </h1>
        <h3><span class="red_star">★</span>/api/phone/check_verify_code [[post]] Test</h3>
        <form action="/api/test/info" method="post">
            <input type="submit">
        </form>


    </body>
    </html>
    <?php
} else {

}