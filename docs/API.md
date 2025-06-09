# SpectraHost API Documentation

## Authentication

All protected endpoints require a valid session. Authentication is handled via PHP sessions.

### Login
```
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password",
    "csrf_token": "token"
}
```

### Register
```
POST /api/register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password",
    "firstName": "John",
    "lastName": "Doe",
    "csrf_token": "token"
}
```

### Logout
```
POST /api/logout
```

## Services

### Get Available Services
```
GET /api/services

Response:
{
    "success": true,
    "services": [
        {
            "id": 1,
            "name": "Web Starter",
            "type": "webhosting",
            "description": "Perfect for small websites",
            "price": 4.99,
            "cpu_cores": 1,
            "memory_gb": 1,
            "storage_gb": 10,
            "bandwidth_gb": 100,
            "features": {
                "php": "8.2",
                "mysql": true,
                "ssl": true
            }
        }
    ]
}
```

## Orders

### Create Order
```
POST /api/order
Content-Type: application/json

{
    "serviceId": 1,
    "billingPeriod": "monthly",
    "serverName": "my-server",
    "csrf_token": "token"
}

Response:
{
    "success": true,
    "orderId": 123,
    "paymentUrl": "https://checkout.mollie.com/...",
    "amount": 4.99
}
```

## User Services

### Get User Services
```
GET /api/user/services

Response:
{
    "success": true,
    "services": [
        {
            "id": 1,
            "server_name": "web-1-123456",
            "status": "active",
            "service_name": "Web Starter",
            "expires_at": "2024-02-01",
            "proxmox_vmid": 101
        }
    ]
}
```

### Get User Orders
```
GET /api/user/orders

Response:
{
    "success": true,
    "orders": [
        {
            "id": 1,
            "service_name": "Web Starter",
            "total_amount": 4.99,
            "status": "paid",
            "created_at": "2024-01-01 12:00:00"
        }
    ]
}
```

## Server Control

### Control Server
```
POST /api/servers/control
Content-Type: application/json

{
    "serviceId": 1,
    "action": "restart"
}

Actions: start, stop, restart

Response:
{
    "success": true,
    "message": "Server wird neugestartet"
}
```

## Payment Webhooks

### Mollie Webhook
```
POST /api/payment/webhook
Content-Type: application/json

{
    "id": "tr_payment_id"
}
```

## Error Responses

All endpoints return errors in this format:
```json
{
    "success": false,
    "error": "Error message"
}
```

### HTTP Status Codes
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Rate Limiting

- Login attempts: 5 per minute per IP
- API calls: 100 per minute per authenticated user
- Registration: 3 per hour per IP

## Security

- All endpoints use CSRF protection
- SQL injection protection via prepared statements
- Password hashing with bcrypt
- Secure session configuration
- Input validation and sanitization