<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dostro CRM API</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
</head>
<body>

<div class="container">
    <h1>🚀 Dostro CRM API</h1>
    <div class="badge">Laravel 13 • Webhook API</div>

    <div class="card">
        <h2>📡 Overview</h2>
        <p>
            This API receives lead data from WordPress websites via <strong>WPForms + Zapier</strong>
            and stores it in a centralized database for CRM usage.
        </p>
    </div>

    <div class="card">
        <h2>🔌 API Endpoints</h2>

        <div class="endpoint">
            <span class="method post">POST</span>
            <code>/api/webhook/leads</code>
            <p>Receive lead data from Zapier webhook</p>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <code>/api/leads</code>
            <p>Retrieve all leads</p>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span>
            <code>/api/leads/{client_id}</code>
            <p>Retrieve leads for a specific client</p>
        </div>
    </div>

    <div class="card">
        <h2>🧪 Example Payload</h2>
        <code>
            {
            "client_id": "client_1",
            "name": "John Smith",
            "email": "john@example.com",
            "message": "Need a quote"
            }
        </code>
    </div>

    <div class="card">
        <h2>⚙️ How It Works</h2>
        <p>
            WordPress → Zapier → Laravel Webhook → Database → CRM Frontend
        </p>
    </div>

    <div class="footer">
        Built by Chetan Nagar
    </div>
</div>

</body>
</html>