# Rate Limiting Configuration

## Default Limits

- **API Endpoints**: 60 requests per minute
- **Webhooks**: 100 requests per minute
- **Login Attempts**: 5 attempts per 15 minutes
- **Registration**: 3 attempts per hour

## Configuration

Edit `.env`:
```env
API_RATE_LIMIT=60
API_RATE_LIMIT_DECAY=1
WEBHOOK_RATE_LIMIT=100
LOGIN_RATE_LIMIT=5
```

## Response Headers

All rate-limited responses include:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
