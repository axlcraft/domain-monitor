# Quick Start: Using the TLD Import Logs

## 📁 Log File Location

Your TLD import logs are here:
```
logs/tld_import_2025-10-08.log  (today's log)
```

## 🔍 Quick Commands

### Watch import in real-time (Windows PowerShell):
```powershell
Get-Content logs\tld_import_2025-10-08.log -Wait -Tail 50
```

### Check for errors:
```powershell
Select-String -Path logs\tld_import_*.log -Pattern "ERROR|CRITICAL"
```

### Find last processed TLD:
```powershell
Select-String -Path logs\tld_import_2025-10-08.log -Pattern "Processing TLD" | Select-Object -Last 1
```

### Check completion status:
```powershell
Select-String -Path logs\tld_import_2025-10-08.log -Pattern "Batch statistics|complete"
```

## 🚨 If Import Fails

1. **Check the log file** for the date of your import
2. **Look for the last line** - it should show which TLD was being processed
3. **Search for "ERROR" or "FAILED"** to find problems
4. **Check "last_processed_id"** to see where it stopped

## 📊 What the Logs Show

- ✅ Each TLD being processed with ID
- ✅ Time taken per TLD (in milliseconds)
- ✅ Success/failure status
- ✅ Data found (WHOIS server, registry URL, dates)
- ✅ Progress tracking (processed/remaining)
- ✅ Batch statistics (total time, average per TLD)
- ✅ Detailed error messages with context

## 🔧 Next Steps After Your Import

1. **Run your import again** - the logging is now active
2. **Watch the logs** while it runs
3. **If it times out** - check the log to see where
4. **Share the log file** if you need help debugging

## 📖 Full Documentation

See `logs/README.md` for complete documentation and troubleshooting guide.
See `LOGGING_SYSTEM.md` for technical details about the implementation.

