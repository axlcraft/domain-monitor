<?php
/**
 * CAPTCHA Widget Component
 * Renders the appropriate CAPTCHA widget based on settings
 * 
 * Required variables:
 * - $captchaSettings: Array with 'provider' and 'site_key'
 */

$provider = $captchaSettings['provider'] ?? 'disabled';
$siteKey = $captchaSettings['site_key'] ?? '';

if ($provider === 'disabled' || empty($siteKey)) {
    return; // No CAPTCHA to render
}
?>

<!-- CAPTCHA Widget -->
<div class="captcha-container mb-4">
    <?php if ($provider === 'recaptcha_v2'): ?>
        <!-- reCAPTCHA v2 -->
        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <?php elseif ($provider === 'recaptcha_v3'): ?>
        <!-- reCAPTCHA v3 (Invisible) -->
        <input type="hidden" id="captcha_response" name="captcha_response">
        <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($siteKey) ?>"></script>
        
    <?php elseif ($provider === 'turnstile'): ?>
        <!-- Cloudflare Turnstile -->
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <?php endif; ?>
</div>

<?php if ($provider === 'recaptcha_v3'): ?>
<!-- reCAPTCHA v3 Form Submission Handler -->
<script>
    // Store the original form submission handler
    const form = document.querySelector('form');
    const originalSubmit = form.onsubmit;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        grecaptcha.ready(function() {
            grecaptcha.execute('<?= htmlspecialchars($siteKey) ?>', {action: 'submit'}).then(function(token) {
                document.getElementById('captcha_response').value = token;
                
                // Call original submit handler if it exists
                if (originalSubmit && originalSubmit.call(form, e) === false) {
                    return;
                }
                
                // Submit the form
                form.submit();
            });
        });
    });
</script>
<?php elseif ($provider === 'recaptcha_v2' || $provider === 'turnstile'): ?>
<!-- reCAPTCHA v2 / Turnstile Response Handler -->
<script>
    // Add hidden input to capture response
    const form = document.querySelector('form');
    const captchaInput = document.createElement('input');
    captchaInput.type = 'hidden';
    captchaInput.name = 'captcha_response';
    captchaInput.id = 'captcha_response';
    form.appendChild(captchaInput);
    
    // Capture response on form submit
    form.addEventListener('submit', function(e) {
        <?php if ($provider === 'recaptcha_v2'): ?>
        const response = grecaptcha.getResponse();
        <?php else: // turnstile ?>
        const response = turnstile.getResponse();
        <?php endif; ?>
        
        captchaInput.value = response;
    });
</script>
<?php endif; ?>

