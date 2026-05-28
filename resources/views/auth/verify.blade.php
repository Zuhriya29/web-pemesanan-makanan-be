<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        a{
            color: white!important;
            font-weight: bold !important;
            text-decoration: none !important;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #F5C518;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            margin: 20px 0;
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #F5C518;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 2rem 0;
        }

        .footer {
            text-align: center!important;
            color: #777;
            font-size: 14px;
        }

        .ii a[href] {
            color: white !important;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Reset Your Password</h2>
        </div>
        <div class="content">
            <p>Halo Pelanggan Setia Griya Dhahar Suroboyo </p>
            <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun kamu. Klik tombol di bawah ini untuk melanjutkan</p>

            <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            
             <p class="expire-note">⚠️ Link ini akan kedaluwarsa dalam 60 menit.</p>

            <p class="footer">
                Jika kamu tidak merasa melakukan permintaan ini, abaikan email ini. 
                Kata sandi kamu tidak akan berubah.
            </p>
        </div>
    </div>
</body>

</html>