# Database Migrations Guide

## Before Running in Production
1. Backup database: `docker exec billing-panel-db mysqldump -u root -p billing > backup.sql`
2. Test in staging first
3. Review migration files for breaking changes

## Rollback Procedure
If migration fails:
1. `php artisan migrate:rollback --step=1`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Restore backup if needed: `docker exec -i billing-panel-db mysql -u root -p billing < backup.sql`

## Critical Migrations
- `2025_12_26_000014_prevent_paid_invoice_delete.php` - Creates immutability trigger for paid invoices
