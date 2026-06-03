/**
 * Rebel Email-OTP — progressive enhancement (vanilla JS, no dependencies).
 * The pages work even WITHOUT this file (plain POST forms); here we only add
 * conveniences: normalising the pasted code and the resend countdown.
 */
(function () {
    'use strict';

    // OTP input: keep digits only, handle paste, auto-submit at full length.
    document.querySelectorAll('[data-rebel-otp-input]').forEach(function (input) {
        var max = parseInt(input.getAttribute('maxlength') || '6', 10);

        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D+/g, '').slice(0, max);
        });

        input.addEventListener('paste', function (event) {
            var clipboard = (event.clipboardData || window.clipboardData);
            var text = clipboard ? clipboard.getData('text') : '';
            var digits = (text || '').replace(/\D+/g, '').slice(0, max);

            if (!digits) {
                return;
            }

            event.preventDefault();
            input.value = digits;

            if (digits.length === max && input.form && input.form.requestSubmit) {
                input.form.requestSubmit();
            }
        });
    });

    // Resend countdown: disable the button for N seconds.
    document.querySelectorAll('[data-rebel-otp-verify]').forEach(function (card) {
        var cooldown = parseInt(card.getAttribute('data-resend-cooldown') || '30', 10);
        var button = card.querySelector('[data-rebel-resend]');
        var label = card.querySelector('[data-rebel-countdown]');

        if (!button) {
            return;
        }

        var remaining = cooldown;
        button.disabled = true;

        (function tick() {
            if (remaining <= 0) {
                button.disabled = false;
                if (label) {
                    label.textContent = '';
                }
                return;
            }

            if (label) {
                label.textContent = '(' + remaining + 's)';
            }

            remaining -= 1;
            setTimeout(tick, 1000);
        })();
    });
})();
