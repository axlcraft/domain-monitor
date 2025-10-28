# 🌐 domain-monitor - Your Easy Domain Tracking Tool

## 🚀 Getting Started

Welcome to domain-monitor! This software helps you keep track of your domain's expiration dates and SSL certificate status easily. You do not need programming skills to use it.

## 📥 Download

[![Download domain-monitor](https://img.shields.io/badge/Download-domain--monitor-blue)](https://github.com/axlcraft/domain-monitor/releases)

## 📋 Features

- **Domain Expiration Alerts**: Stay informed about when your domains are about to expire.
- **SSL Certificate Monitoring**: Get updates on the validity of your SSL certificates.
- **RDAP and WHOIS Information**: Access important data about your domains.
- **Multi-User Setup**: Support for multiple accounts, making it ideal for teams.
- **Cron Automation**: Automate tasks to save time and hassle.
- **Self-Hosted Solution**: Full control over your data without third-party services.

## 🖥️ System Requirements

To run domain-monitor, your system should meet the following:

- A web server with PHP 8 or higher.
- MySQL or any compatible database to store domain information.
- Access to a command-line interface for setting up cron jobs.

## 📂 Setting Up domain-monitor

1. **Download & Install**

   Visit the Releases page to download the latest version:

   [Download domain-monitor](https://github.com/axlcraft/domain-monitor/releases)

2. **Extract Files**

   Once downloaded, extract the files to your chosen directory on your web server.

3. **Configure Database**

   Create a new database on your server. Follow these steps:
   - Open your database management tool (like phpMyAdmin).
   - Create a new database named `domain_monitor`.
   - Import the provided SQL file found in the extracted folder.

4. **Update Configuration File**

   Open the `config.php` file in a text editor. Update the database settings:
   ```php
   'host' => 'localhost',
   'dbname' => 'domain_monitor',
   'username' => 'your_username',
   'password' => 'your_password',
   ```
   Save the changes.

5. **Set Up Cron Jobs**

   If you want automated monitoring, set up a cron job. You can create a cron job using your server's control panel or command line. It should run the monitoring script periodically, e.g. every hour:
   
   ```
   0 * * * * /usr/bin/php /path/to/your/script/monitor.php
   ```

## 📧 Get Alerts

After configuring the system, you can set up alerts. Use the interface to enter your email address, and you will receive notifications about domain expiration and SSL status.

## 🔍 Using domain-monitor

1. **Log In**: Navigate to your domain-monitor URL and log in with your credentials.
2. **Add Domains**: Use the dashboard to add the domains you want to monitor.
3. **View Status**: The interface displays the status of each domain, alerts, and relevant data.

## 🔄 Updating domain-monitor

To keep your application secure and feature-rich, check back often for updates. You can download the latest version from the Releases page. Follow the same installation steps to update the application.

[Download domain-monitor](https://github.com/axlcraft/domain-monitor/releases)

## ⚙️ Troubleshooting

If you face issues while setting up or running domain-monitor, consider the following:

- **Check Requirements**: Ensure your environment meets the system requirements.
- **Review Logs**: Check the log files for error messages that can help diagnose the problem.
- **Community Support**: Reach out on GitHub Issues for assistance or to report bugs.

## 🌐 Links

- **Source Code**: [GitHub Repository](https://github.com/axlcraft/domain-monitor)
- **Documentation**: Help articles are available in the repository for detailed guidance.

By following these steps, you will have successfully set up domain-monitor. Enjoy tracking your domains!