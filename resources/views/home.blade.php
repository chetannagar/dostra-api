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
            <h1>Dostro CRM API</h1>
            <div class="badge">Laravel 13 • Webhook + CRM API</div>

            <div class="card">
                <h2>Overview</h2>
                <p>
                    This API receives lead data from WordPress websites via <strong>WPForms + Zapier</strong>
                    and stores it in a centralized database for CRM usage.
                    It also provides authenticated CRM endpoints for admins and client users.
                </p>
            </div>

            <div class="card">
                <h2>Public Webhook</h2>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/api/webhook/leads</code>
                    <p>Receives signed lead data from Zapier. Requires <code>X-Client-Key</code>, <code>X-Webhook-Id</code>, <code>X-Webhook-Timestamp</code>, and <code>X-Webhook-Signature</code>.</p>
                </div>
            </div>

            <div class="card">
                <h2>CRM Auth</h2>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/api/login</code>
                    <p>Returns a Sanctum bearer token for the React CRM.</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/api/me</code>
                    <p>Returns the authenticated user and assigned clients.</p>
                </div>

                <div class="endpoint">
                    <span class="method post">POST</span>
                    <code>/api/logout</code>
                    <p>Revokes the current bearer token.</p>
                </div>
            </div>

            <div class="card">
                <h2>CRM Resources</h2>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/api/leads</code>
                    <p>Returns paginated leads. Supports <code>client_id</code>, <code>email</code>, <code>search</code>, <code>from</code>, <code>to</code>, and <code>per_page</code>.</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/api/leads/{lead}</code>
                    <p>Returns a single lead by database id.</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/api/clients</code>
                    <p>Admin sees all clients. Client users only see assigned clients.</p>
                </div>

                <div class="endpoint">
                    <span class="method get">GET</span>
                    <code>/api/users</code>
                    <p>Admin-only user management endpoint.</p>
                </div>
            </div>

            <div class="card">
                <h2>Webhook Payload</h2>
                <code>
                    {
                    "name": "John Smith",
                    "email": "john@example.com",
                    "message": "Need a quote"
                    }
                </code>
            </div>

            <div class="card">
                <h2>Access Model</h2>
                <p>
                    Admin users can manage all clients, users, and leads. Client users can only access their assigned clients and those clients' leads.
                </p>
            </div>

            <div class="card">
                <h2>How It Works</h2>
                <p>
                    WordPress -> Zapier -> Signed Laravel Webhook -> Database -> React CRM
                </p>
            </div>

            <div class="footer">
                Built by Chetan Nagar
            </div>
        </div>
    </body>
</html>
