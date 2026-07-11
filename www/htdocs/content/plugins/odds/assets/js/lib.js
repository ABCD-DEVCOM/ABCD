/**
 * ODDS Modernized lib.js
 * Replaces the legacy XMLHttpRequest/ActiveXObject with the modern Fetch API.
 * @package ABCD_Plugins_ODDS
 */

async function getOutput(email, email_apoderado, fecha, name, status, uploadFiles, notes, title) {
    const url = '/service/odds/?action=send_email';

    // Prepare data safely using URLSearchParams
    const params = new URLSearchParams({
        email: email || '',
        email_apoderado: email_apoderado || '',
        fecha: fecha || '',
        name: name || '',
        status: status || '',
        uploadFiles: uploadFiles || '',
        notes: notes || '',
        title: title || ''
    });

    try {
        const response = await fetch(url, {
            method: 'POST', // POST is safer for emails and avoids URL length limits
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const responseText = await response.text();
        drawOutput(responseText);
    } catch (error) {
        console.error('ODDS Fetch error:', error);
        drawError();
    }

    return false;
}

function drawError() {
    const container = document.getElementById('output');
    if (container) {
        container.innerHTML = '<span style="color:red; font-family:Verdana;">Error processing the request. Check console.</span>';
    }
}

function drawOutput(responseText) {
    const container = document.getElementById('output');
    if (container) {
        container.innerHTML = responseText;
    }
}