# Logs Directory

This directory contains detailed application logs for debugging and monitoring purposes.

## Log Files

### TLD Import Logs (`tld_import_YYYY-MM-DD.log`)

Comprehensive logs for TLD registry import operations including:

- **Start/End Operations**: Marked with `=== START:` and `=== END:` separators
- **Batch Processing**: Each batch's start time, TLDs processed, and duration
- **Individual TLD Processing**: 
  - TLD name and ID
  - Fetch time (in milliseconds)
  - Database update time
  - Data found (WHOIS server, registry URL, dates)
  - Success/failure status
  - Error messages with exception details
- **Progress Tracking**:
  - Current step in workflow
  - Last processed ID
  - Completion percentage
  - Remaining TLDs
- **Statistics**:
  - Batch processing time
  - Average time per TLD
  - Success/failure counts
  - Overall progress

### Cron Job Logs (`cron.log`)

Logs from automated domain checking cron jobs (created by `cron/check_domains.php`).

## Log Levels

- **DEBUG**: Detailed diagnostic information
- **INFO**: General informational messages (normal operations)
- **WARNING**: Warning messages (non-critical issues)
- **ERROR**: Error messages (failures that don't stop execution)
- **CRITICAL**: Critical errors (failures that stop execution)

## Log Format

```
[YYYY-MM-DD HH:MM:SS] [LEVEL] Message | Context: {"key":"value"}
```

Example:
```
[2025-10-08 10:04:23] [INFO] Processing TLD [1/50]: .aaa (ID: 1)
[2025-10-08 10:04:23] [INFO] TLD .aaa: SUCCESS - Found WHOIS server, registry URL | Context: {"fetch_time_ms":245.67,"update_time_ms":12.34,"data_fields":2}
```

## Troubleshooting TLD Import Issues

### Issue: Import Stuck at Same Progress

**Check the logs for:**
1. Look for "last_processed_id" - is it advancing?
2. Check for FAILED messages - which TLDs are failing?
3. Look at "batch_time_seconds" - is it taking too long?

**Example log analysis:**
```bash
# Find failed TLDs
grep "FAILED" logs/tld_import_2025-10-08.log

# Check last processed IDs
grep "Updating last processed ID" logs/tld_import_2025-10-08.log

# Find slow TLDs (over 5 seconds)
grep "fetch_time_ms" logs/tld_import_2025-10-08.log | grep -E '"fetch_time_ms":[5-9][0-9]{3}|[0-9]{5,}'
```

### Issue: FastCGI Timeout

**Symptoms in logs:**
- Last log entry shows a TLD being processed
- No "Batch statistics" or "END:" marker
- Apache/Nginx error log shows "Connection reset by peer"

**Solutions:**
1. Reduce batch size in `TldRegistryService.php`:
   ```php
   $tldsNeedingWhois = $this->getTldsNeedingWhoisData(25, $lastProcessedId); // Reduced from 50
   ```

2. Increase PHP/FastCGI timeout:
   ```ini
   ; php.ini
   max_execution_time = 300
   ```

### Issue: Repeated Failures on Same TLDs

**Check logs for:**
```bash
# Find TLDs that consistently fail
grep "FAILED" logs/tld_import_*.log | sort | uniq -c | sort -rn
```

**Common causes:**
- TLD doesn't have IANA RDAP/WHOIS data
- Network timeout to IANA servers
- Invalid TLD format

## Analyzing Performance

### Average Processing Time
```bash
# Get average fetch time per TLD
grep "fetch_time_ms" logs/tld_import_2025-10-08.log | grep -oP '"fetch_time_ms":\K[0-9.]+' | awk '{sum+=$1; n++} END {print "Average: " sum/n " ms"}'
```

### Slowest TLDs
```bash
# Find slowest 10 TLDs
grep "fetch_time_ms" logs/tld_import_2025-10-08.log | grep -oP 'TLD \.\w+.*fetch_time_ms":\K[0-9.]+' | sort -rn | head -10
```

### Batch Performance
```bash
# Show all batch statistics
grep "Batch statistics" logs/tld_import_2025-10-08.log
```

## Log Retention

Logs are organized by date and are not automatically deleted. You may want to:

1. **Manual cleanup**: Delete old logs periodically
   ```bash
   find logs/ -name "*.log" -mtime +30 -delete  # Delete logs older than 30 days
   ```

2. **Set up logrotate** (Linux):
   ```
   /path/to/Domain Monitor/logs/*.log {
       daily
       rotate 30
       compress
       missingok
       notifempty
   }
   ```

## Accessing Logs

### Via Command Line
```bash
# View latest TLD import log
tail -f logs/tld_import_$(date +%Y-%m-%d).log

# View last 100 lines
tail -100 logs/tld_import_2025-10-08.log

# Search for errors
grep ERROR logs/tld_import_2025-10-08.log

# Watch for specific TLD
grep ".example" logs/tld_import_2025-10-08.log
```

### Via PHP
The `Logger` class provides a `tail()` method:
```php
$logger = new Logger('tld_import');
$lastLines = $logger->tail(100); // Get last 100 lines
```

## Important Notes

- **Log files grow large**: A complete TLD import (~1500 TLDs) generates ~2-5 MB of logs
- **Context data is JSON**: Can be parsed programmatically for analysis
- **Timestamps are in server timezone**: Check your PHP timezone setting
- **Logs are NOT web-accessible**: The `.htaccess` in the project root blocks access to this directory

## Related Files

- `app/Services/Logger.php` - Logger implementation
- `app/Services/TldRegistryService.php` - Uses logger for TLD import operations
- `cron/check_domains.php` - Uses file_put_contents for cron.log

## Support

If you're experiencing import issues:
1. Check the relevant log file for error messages
2. Look for patterns in failed TLDs
3. Check Apache/Nginx error logs for timeout issues
4. Verify network connectivity to IANA servers
5. Consider reducing batch size for slower servers

