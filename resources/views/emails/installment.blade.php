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
        .button {
            display: inline-block;
            background-color: #0044cc;
            color: #fff !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        .button:hover {
            background-color: #003399;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        .barcode {
            background: #f2f2f2;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="email-container">

        <h1>Olá, <strong>{{ $installment->client->name }}</strong>!</h1>

        <p>
            Sua parcela <strong>#{{ $installment->installment_number }}</strong> já está disponível.
        </p>

        <p>
            <strong>Valor:</strong> R$ {{ number_format($installment->amount, 2, ',', '.') }}<br>
            <strong>Vencimento:</strong> {{ $installment->due_date->format('d/m/Y') }}
        </p>

        <p>
            <a href="{{ $installment->boleto_url }}" class="button">
                Visualizar e pagar boleto
            </a>
        </p>

        <p>
            <strong>Código de barras:</strong>
        </p>

        <div class="barcode">
            {{ $installment->boleto_barcode }}
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ env('APP_NAME') }}. Todos os direitos reservados.</p>
        </div>

    </div>
</body>
</html>