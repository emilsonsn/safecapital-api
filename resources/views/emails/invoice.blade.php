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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        td.amount, th.amount {
            text-align: right;
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

        <h1>Olá, <strong>{{ $user->company_name ?: trim($user->name.' '.$user->surname) }}</strong>!</h1>

        <p>
            Sua fatura mensal já está disponível.
        </p>

        <p>
            <strong>Valor total:</strong> R$ {{ number_format($invoice->amount, 2, ',', '.') }}<br>
            <strong>Vencimento:</strong> {{ $invoice->due_date->format('d/m/Y') }}
        </p>

        <p><strong>Contratos inclusos nesta fatura:</strong></p>

        <table>
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th class="amount">Valor da parcela</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->installments as $installment)
                    <tr>
                        <td>{{ trim($installment->client->name.' '.$installment->client->surname) }}</td>
                        <td class="amount">R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>
            <a href="{{ $invoice->boleto_url }}" class="button">
                Visualizar e pagar boleto
            </a>
        </p>

        <p>
            <strong>Código de barras:</strong>
        </p>

        <div class="barcode">
            {{ $invoice->boleto_barcode }}
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ env('APP_NAME') }}. Todos os direitos reservados.</p>
        </div>

    </div>
</body>
</html>
