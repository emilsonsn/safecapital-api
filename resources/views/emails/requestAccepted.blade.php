<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        p a {
            display: inline-block;
            background-color: #0044cc;
            color: #fff !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        p a:hover {
            background-color: #003399;
        }
        .email a, .email a:hover{
            background: none;
            color: #333 !important;
            padding: 0 !important;
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
        <h1>Seja bem-vindo, <strong>{{ $name }}</strong>! Sua solicitação de cadastro foi aceita!</h1>
        <p>Agora você já pode acessar seu ambiente no nosso sistema.</p>
        <p>Para ter acesso user seu email e senha criados no cadastro</p>
        <ul>
            <li><strong>Link para o sistema:</strong> <a href="{{ env('FRONT_URL') }}">Fazer primeiro acesso</a></li>
        </ul>

        <p>
            Em anexo consta nossos termos de aceite e policitas de privacidade.
        </p>
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>