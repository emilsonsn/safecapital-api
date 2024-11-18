<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #0044cc;
            font-size: 24px;
        }
        p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        a {
            display: inline-block;
            background-color: #0044cc;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        a:hover {
            background-color: #003399;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>Recuperação de Senha</h1>
        <p>Olá,</p>
        <p>Você solicitou a recuperação de senha. Use o botão abaixo para redefinir sua senha:</p>
        <p><a href="{{env('FRONT_URL') . '/password_recovery?code=' . $code }}">Recuperar Senha</a></p>
        <p>Se você não solicitou essa recuperação, por favor ignore este e-mail.</p>
        <div class="footer">
            <p>© {{ date('Y') }} {{ env('APP_NAME') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
