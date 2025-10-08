# Changelog

All notable changes to Domain Monitor will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- TLD Registry System with IANA integration
  - Import and manage TLD data (RDAP servers, WHOIS servers, registry URLs)
  - Progressive import workflow with real-time progress tracking
  - Support for 1,400+ TLDs with automatic updates
  - Import logs and history tracking
- Advanced domain verification using TLD registry data
- RDAP protocol support for modern domain queries
- Automatic WHOIS server discovery per TLD
- Monitoring status change notifications (activated/deactivated alerts)
- Notification group assignment change alerts
- Enhanced domain detail view with channel status indicators
- Comprehensive notification threshold configuration
- Debug logging for notification thresholds

### Changed
- Unified design system across all views
  - Consistent header styles (bordered instead of gradients)
  - Standardized button sizes and padding
  - Consistent form input styling
  - Unified empty state designs
  - Removed emojis from UI elements
- Improved navigation flow (edit page returns to detail view)
- Enhanced cron job logging with threshold display
- Streamlined installation process
  - Encryption key auto-generation during migration
  - No separate script needed for encryption key setup

### Fixed
- Notification channel type display error in domain view
- Navigation redirect after domain update
- Cancel button redirect in domain edit page
- Design inconsistencies in notification group views

### Security
- Random secure password generation on installation
- One-time password display during migration
- Removed hardcoded default credentials
- 16-character cryptographically secure admin passwords

## [1.0.0] - 2024-10-08

### Added
- Initial release of Domain Monitor
- Modern PHP 8.1+ MVC architecture
- Domain management system with CRUD operations
- Automatic WHOIS lookup for domain information
- Multi-channel notification system:
  - Email notifications via PHPMailer
  - Telegram bot integration
  - Discord webhook support
  - Slack webhook support
- Notification groups feature
- Assign domains to notification groups
- Dashboard with real-time statistics
- Domain status tracking (active, expiring_soon, expired, error)
- Notification logging system
- Customizable notification intervals
- Cron job for automated domain checks
- Test notification script
- Responsive, modern UI design
- Database migration system
- Comprehensive documentation
- Installation guide
- User authentication system
- Security features (prepared statements, session management)

### Features
- ✅ Add, edit, delete, and view domains
- ✅ Automatic expiration date detection via WHOIS
- ✅ Support for multiple notification channels per group
- ✅ Flexible notification scheduling (60,30, 15, 7, 3, 1 days before)
- ✅ Email notifications with HTML templates
- ✅ Rich Discord embeds with color coding
- ✅ Telegram messages with formatting
- ✅ Slack blocks for structured messages
- ✅ Notification deduplication (prevent spam)
- ✅ Manual domain refresh
- ✅ Active/inactive domain toggle
- ✅ Comprehensive logging
- ✅ Statistics dashboard
- ✅ Recent notifications view
- ✅ Domain details with WHOIS data
- ✅ Nameserver display
- ✅ Notification history per domain

### Technical
- PHP 8.1+ with modern features (match expressions, typed properties)
- MySQL/MariaDB database
- PSR-4 autoloading
- Environment-based configuration
- MVC pattern implementation
- Service layer architecture
- Repository pattern for data access
- Interface-based notification channels
- JSON configuration storage
- Prepared statements for SQL injection prevention
- CSRF token support ready
- Responsive CSS with CSS variables
- No JavaScript framework dependencies (vanilla JS where needed)

### Documentation
- README.md with comprehensive guide
- INSTALL.md with step-by-step installation
- Inline code documentation
- Configuration examples
- Troubleshooting guide

### Future Enhancements (Roadmap)
- [ ] User authentication system
- [ ] Multi-user support with permissions
- [ ] API for external integrations
- [ ] Domain grouping/tagging
- [ ] Custom notification templates
- [ ] SMS notifications (Twilio)
- [ ] WhatsApp notifications
- [ ] Export functionality (CSV, PDF)
- [ ] Import domains from CSV
- [ ] Domain transfer tracking
- [ ] DNS record monitoring
- [ ] SSL certificate monitoring
- [ ] Downtime monitoring
- [ ] 2FA for login
- [ ] Mobile app
- [ ] Docker support
- [ ] Redis caching
- [ ] Rate limiting
- [ ] Webhook support for third-party integrations
- [ ] Dark mode UI toggle
- [ ] Multi-language support
- [ ] Advanced filtering and search
- [ ] Bulk operations
- [ ] Scheduled reports
- [ ] Integration with domain registrars

---

## Version History

### 1.0.0 (2024-10-08)
- Initial public release
- Created by [Hosteroid](https://www.hosteroid.uk) - Premium Hosting Solutions

