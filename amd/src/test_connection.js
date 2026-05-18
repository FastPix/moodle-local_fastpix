// Test-connection button binding for the local_fastpix admin settings page.
//
// Wires a click on the button (id passed in `buttonId`) to a Moodle AJAX
// call against `local_fastpix_test_connection`. Updates the status span
// (id `statusId`) with the result. Never throws — any rejection from the
// AJAX layer is rendered into the status span as a red message so the
// admin sees a real signal rather than a console error.
//
// Loaded by settings.php via $PAGE->requires->js_call_amd().

define(['core/ajax', 'core/str'], function(Ajax, Str) {
    'use strict';

    /**
     * @param {string} buttonId
     * @param {string} statusId
     */
    function bind(buttonId, statusId) {
        var button = document.getElementById(buttonId);
        var status = document.getElementById(statusId);
        if (!button || !status) {
            return;
        }
        if (button.dataset.fpBound === '1') {
            return;
        }
        button.dataset.fpBound = '1';

        button.addEventListener('click', function() {
            Str.get_strings([
                {key: 'test_connection_running', component: 'local_fastpix'},
                {key: 'test_connection_success', component: 'local_fastpix'},
                {key: 'test_connection_failed',  component: 'local_fastpix'},
            ]).then(function(strs) {
                var running = strs[0];
                var successTpl = strs[1];
                var failedTpl = strs[2];

                status.textContent = running;
                status.style.color = '';
                button.disabled = true;

                Ajax.call([{
                    methodname: 'local_fastpix_test_connection',
                    args: {}
                }])[0].then(function(result) {
                    button.disabled = false;
                    if (result.success) {
                        status.textContent = successTpl.replace('{$a}', result.latency_ms);
                        status.style.color = 'green';
                    } else {
                        var msg = result.error || 'unknown';
                        status.textContent = failedTpl.replace('{$a}', msg);
                        status.style.color = 'red';
                    }
                    return result;
                }).catch(function(err) {
                    button.disabled = false;
                    var msg = (err && (err.message || err.error)) || String(err);
                    status.textContent = failedTpl.replace('{$a}', msg);
                    status.style.color = 'red';
                });
                return strs;
            }).catch(function(err) {
                status.textContent = 'Failed: ' + (err.message || err);
                status.style.color = 'red';
                button.disabled = false;
            });
        });
    }

    return {
        init: bind
    };
});
