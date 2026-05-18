// Send-test-event button binding for the local_fastpix admin settings page.
// Mirrors test_connection.js — click → AJAX call → inline status render.

define(['core/ajax', 'core/str'], function(Ajax, Str) {
    'use strict';

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
                {key: 'send_test_event_running', component: 'local_fastpix'},
                {key: 'send_test_event_success', component: 'local_fastpix'},
                {key: 'send_test_event_failed',  component: 'local_fastpix'},
            ]).then(function(strs) {
                var running = strs[0];
                var successTpl = strs[1];
                var failedTpl = strs[2];

                status.textContent = running;
                status.style.color = '';
                button.disabled = true;

                Ajax.call([{
                    methodname: 'local_fastpix_send_test_event',
                    args: {}
                }])[0].then(function(result) {
                    button.disabled = false;
                    if (result.success) {
                        status.textContent = successTpl.replace('{$a}', result.ledger_id);
                        status.style.color = 'green';
                    } else {
                        var msg = (result.errors && result.errors.length)
                            ? result.errors.join(', ')
                            : result.result || 'unknown';
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
