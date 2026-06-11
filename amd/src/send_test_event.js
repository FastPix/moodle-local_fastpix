// Send-test-event button binding for the local_fastpix admin settings page.
// Mirrors test_connection.js — click → AJAX call → inline status render.

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

/**
 * Extract a human-readable string from a rejection value without
 * coercing a plain object to "[object Object]".
 *
 * @param {*} err The rejection value (string, Error, or object).
 * @return {String} A best-effort message string.
 */
function errorMessage(err) {
    if (typeof err === 'string') {
        return err;
    }
    if (err && (err.message || err.error)) {
        return err.message || err.error;
    }
    if (err instanceof Error) {
        return err.toString();
    }
    return 'unknown';
}

/**
 * Render a failure message into the status element.
 *
 * @param {HTMLElement} status The status element to render into.
 * @param {String} failedTpl The failure message template containing {$a}.
 * @param {String} msg The message to substitute into the template.
 */
function renderFailure(status, failedTpl, msg) {
    status.textContent = failedTpl.replace('{$a}', msg);
    status.style.color = 'red';
}

/**
 * Handle a successful AJAX response from the send-test-event endpoint.
 *
 * @param {HTMLElement} button The button to re-enable.
 * @param {HTMLElement} status The status element to render into.
 * @param {String} successTpl The success message template containing {$a}.
 * @param {String} failedTpl The failure message template containing {$a}.
 * @param {Object} result The web service result.
 * @return {Object} The unchanged result.
 */
function onResult(button, status, successTpl, failedTpl, result) {
    button.disabled = false;
    if (result.success) {
        status.textContent = successTpl.replace('{$a}', result.ledger_id);
        status.style.color = 'green';
    } else {
        const msg = (result.errors?.length)
            ? result.errors.join(', ')
            : result.result || 'unknown';
        renderFailure(status, failedTpl, msg);
    }
    return result;
}

/**
 * Handle a rejected AJAX call to the send-test-event endpoint.
 *
 * @param {HTMLElement} button The button to re-enable.
 * @param {HTMLElement} status The status element to render into.
 * @param {String} failedTpl The failure message template containing {$a}.
 * @param {Object} err The rejection error.
 */
function onResultError(button, status, failedTpl, err) {
    button.disabled = false;
    renderFailure(status, failedTpl, errorMessage(err));
}

/**
 * Fire the send-test-event web service call and render the outcome.
 *
 * @param {HTMLElement} button The button bound to the action.
 * @param {HTMLElement} status The status element to render into.
 * @param {Array} strs The resolved language strings (running, success, failed).
 * @return {Promise} The AJAX promise.
 */
function sendEvent(button, status, strs) {
    const running = strs[0];
    const successTpl = strs[1];
    const failedTpl = strs[2];

    status.textContent = running;
    status.style.color = '';
    button.disabled = true;

    return Ajax.call([{
        methodname: 'local_fastpix_send_test_event',
        args: {}
    }])[0]
        .then(function(result) {
            return onResult(button, status, successTpl, failedTpl, result);
        })
        .catch(function(err) {
            onResultError(button, status, failedTpl, err);
        });
}

/**
 * Resolve the language strings and trigger the test event on click.
 *
 * @param {HTMLElement} button The button bound to the action.
 * @param {HTMLElement} status The status element to render into.
 */
function handleClick(button, status) {
    getStrings([
        {key: 'send_test_event_running', component: 'local_fastpix'},
        {key: 'send_test_event_success', component: 'local_fastpix'},
        {key: 'send_test_event_failed', component: 'local_fastpix'},
    ]).then(function(strs) {
        sendEvent(button, status, strs);
        return strs;
    }).catch(function(err) {
        status.textContent = 'Failed: ' + errorMessage(err);
        status.style.color = 'red';
        button.disabled = false;
    });
}

/**
 * Bind the send-test-event handler to the button, once.
 *
 * @param {String} buttonId The DOM id of the trigger button.
 * @param {String} statusId The DOM id of the status element.
 */
export const init = (buttonId, statusId) => {
    const button = document.getElementById(buttonId);
    const status = document.getElementById(statusId);
    if (!button || !status) {
        return;
    }
    if (button.dataset.fpBound === '1') {
        return;
    }
    button.dataset.fpBound = '1';

    button.addEventListener('click', function() {
        handleClick(button, status);
    });
};
